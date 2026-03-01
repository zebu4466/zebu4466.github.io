<?php
session_start();

$servername   = "sql109.infinityfree.com";
$username     = "if0_38847172";
$password     = "Zeboosta4466";
$dbname       = "if0_38847172_speechdeb_users";

// 🔹 Always define these so they exist before any HTML
$guestMode  = (isset($_GET['guest']) && $_GET['guest'] === '1');
$loggedInId = $_SESSION['user_id'] ?? null;
$profileId  = null;
$isOwner    = false;   // ✅ DEFAULT: avoid "undefined variable" warnings

if ($guestMode) {
    // Guest has no PHP session user
    $loggedInId = null;
    $profileId  = null;
    $isOwner    = false;
} else {
    $profileId = $_GET['id'] ?? $loggedInId;
    if ($loggedInId !== null && $profileId !== null) {
        $isOwner = ((int)$profileId === (int)$loggedInId);
    }
}

header("Access-Control-Allow-Origin: https://speechdeb.infy.uk");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$action = $_GET['action'] ?? null;

// === API: UPLOAD PROFILE PICTURE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar']) && isset($_POST['id'])) {
    header("Content-Type: application/json");
    $id = (int) $_POST['id'];

    $file = $_FILES['avatar'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];

    if (!in_array($file['type'], $allowed)) {
        echo json_encode(["error" => "❌ Invalid image type."]);
        exit;
    }

    if ($file['size'] > 2 * 1024 * 1024) { // limit to 2MB
        echo json_encode(["error" => "❌ Image too large (max 2MB)."]);
        exit;
    }

    $conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "❌ DB connection failed."]);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$newName = "avatars/user_" . $id . "_" . time() . "." . $ext;
$targetPath = __DIR__ . "/" . $newName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(["error" => "❌ Upload failed."]);
        exit;
    }

    $url = $newName; // relative path to serve
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo json_encode(["error" => "❌ DB connection failed."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET profile_picture_url = ? WHERE id = ?");
    $stmt->bind_param("si", $url, $id);
    $stmt->execute();

    echo json_encode(["success" => true, "url" => $url]);
    exit;
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Only run session restore logic if not in guest mode and not already in an API call
if (
    !$guestMode &&
    !isset($_SESSION['user_id']) &&
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    !isset($_GET['action'])
) {
    echo <<<HTML
    <!DOCTYPE html>
    <html><body><script>
      const email = localStorage.getItem("speechdeb_email");
      if (!email) {
        document.body.textContent = "❌ Not logged in: Missing email.";
      } else {
        fetch("user.php?action=get&email=" + encodeURIComponent(email))
          .then(res => res.json())
          .then(data => {
            if (!data || !data.id || !data.email) {
              throw new Error("Missing profile data.");
            }
            return fetch("user.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: new URLSearchParams({ id: data.id, email: data.email }),
              credentials: "include"
            });
          })
          .then(() => location.reload())
          .catch(err => {
            document.body.textContent = "❌ Not logged in: " + err.message;
          });
      }
    </script></body></html>
    HTML;
    exit;
}

// === SESSION SYNC ENDPOINT ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['id'])) {
    $_SESSION['user_id'] = (int) $_POST['id'];
    echo "session synced";
    exit;
}

// === API: GET PROFILE ===
if ($action === 'get') {
    header("Content-Type: application/json");
    while (ob_get_level()) ob_end_clean();
    $id = $_REQUEST['id'] ?? null;
    $email = $_REQUEST['email'] ?? null;

    if (!$id && !$email) {
        echo json_encode(["error" => "\u274C Provide id or email."]);
        exit;
    }

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo json_encode(["error" => "\u274C DB connection failed."]);
        exit;
    }

    if ($id) {
        $stmt = $conn->prepare("SELECT id, email, name, bio, profile_picture_url, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $conn->prepare("SELECT id, email, name, bio, profile_picture_url, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
    if (!isset($_GET['action'])) {
        header("Location: 404.html");
        exit;
    }
    echo json_encode(["error" => "\u274C User not found."]);
    exit;
}

    $profile = $result->fetch_assoc();

// If no profile picture is set, use default favicon
if (empty($profile['profile_picture_url'])) {
    $profile['profile_picture_url'] = "https://speechdeb.infy.uk/favicon.png";
}

echo json_encode($profile);
exit;
}

// === API: UPDATE PROFILE ===
if ($action === 'update') {
    header("Content-Type: application/json");
    while (ob_get_level()) ob_end_clean();
    $email = trim($_POST['email'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $bio   = trim($_POST['bio'] ?? '');
    $role  = trim($_POST['role'] ?? 'student');
    $pic   = trim($_POST['profile_picture_url'] ?? '');

    if (!$email) {
        echo json_encode(["error" => "\u274C Missing email."]);
        exit;
    }

    if (!in_array($role, ['student', 'coach', 'judge'])) {
        echo json_encode(["error" => "\u274C Invalid role."]);
        exit;
    }

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        echo json_encode(["error" => "\u274C DB connection failed."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET name = ?, bio = ?, profile_picture_url = ?, role = ? WHERE email = ?");
    $stmt->bind_param("sssss", $name, $bio, $pic, $role, $email);
    $success = $stmt->execute();

    echo json_encode($success ? ["success" => true] : ["error" => "\u274C Update failed."]);
    exit;
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Speechdeb</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .profile-box { display: flex; gap: 20px; align-items: flex-start; padding: 20px; background: #f5f5f5; border-bottom: 1px solid #ccc; }
    #avatarSection { display: flex; flex-direction: column; align-items: center; }
    #profilePic { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; cursor: pointer; }
    .profileField { margin-bottom: 10px; }
    textarea { width: 100%; height: 60px; }
  </style>

<style>
#contentRow {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

#filterSidebar {
  flex: 0 0 20%;
  min-width: 180px;
  max-width: 220px;
  background: #f9f9f9;
  padding: 15px 20px;
  border: 1px solid #ccc;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}

#categoryFilters div {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}

#menuView {
  flex: 1;
}

.filterSection {
  margin-top: 20px;
}

#filterHeader {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

#filterHeader a {
  font-size: 0.9em;
  color: #007bff;
  text-decoration: none;
  cursor: pointer;
}

#filterHeader a:hover {
  text-decoration: underline;
}
</style>

</head>
<body>
<script>
if (!document.cookie.includes("PHPSESSID") && localStorage.getItem("userId")) {
  fetch("session_sync.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      id: localStorage.getItem("userId"),
      email: localStorage.getItem("speechdeb_email")
    }),
    credentials: "include"
  }).then(() => location.reload());
}
</script>

<script>
let activeSort = 'title';
let activeCategories = [];

let originalSpeechBoxes = [];

function updateSpeechDisplay() {
  const menu = document.getElementById("menuView");
  if (!menu) return;

  menu.innerHTML = "";

  let filtered = [...originalSpeechBoxes];

  if (activeCategories.length > 0) {
    filtered = filtered.filter(box =>
      activeCategories.includes(box.getAttribute("data-category"))
    );
  }

  filtered.sort((a, b) => {
    if (activeSort === 'title') {
      return a.getAttribute("data-title").localeCompare(b.getAttribute("data-title"));
    } else {
      return new Date(b.getAttribute("data-updated")) - new Date(a.getAttribute("data-updated"));
    }
  });

  filtered.forEach(box => menu.appendChild(box));

  const label = activeCategories.length > 0 ? activeCategories.join(", ") : "None";
  document.getElementById("activeFilters").textContent = `Filters: ${label}`;
}

function initializeFilters(speechData) {
  const container = document.getElementById("categoryFilters");
  const categories = [...new Set(speechData.map(s => s.category).filter(Boolean))].sort();

if (container) {
  container.innerHTML = "";
}

  categories.forEach(cat => {
    const id = `filter-${cat.replace(/\s+/g, '-')}`;
    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.id = id;
    checkbox.value = cat;

    checkbox.onchange = () => {
      if (checkbox.checked) activeCategories.push(cat);
      else activeCategories = activeCategories.filter(c => c !== cat);
      updateSpeechDisplay();
    };

    const label = document.createElement("label");
    label.htmlFor = id;
    label.textContent = cat;

const wrapper = document.createElement("div");
wrapper.style.display = "flex";
wrapper.style.alignItems = "center";
wrapper.style.gap = "8px";
wrapper.style.marginBottom = "6px";

wrapper.appendChild(checkbox);
wrapper.appendChild(label);

if (container) {
container.appendChild(wrapper);
}
  });

  document.querySelectorAll("input[name='sortOption']").forEach(radio => {
    radio.onchange = () => {
      activeSort = radio.value;
      updateSpeechDisplay();
    };
  });

  document.getElementById("clearFilters").onclick = (e) => {
    e.preventDefault();
    activeCategories = [];
    document.querySelectorAll("#categoryFilters input[type='checkbox']").forEach(cb => cb.checked = false);
    updateSpeechDisplay();
  };
}
</script>
  <div id="mainContainer">
    <div id="profileBox" class="profile-box">
<div id="avatarSection">
  <div style="position: relative; width: 100px; height: 100px;">
    <img id="profilePic" src="favicon.png"
         alt="Profile Picture"
         style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;"
         onerror="this.onerror=null; this.src='favicon.png';" />
    <?php if ($isOwner): ?>
      <input type="file" id="uploadAvatar" accept="image/*"
             style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;
                    opacity: 0; cursor: pointer; z-index: 10;" />
    <?php endif; ?>
  </div>
</div>
      <div id="profileInfo">
        <div class="profileField">
          <label>Name:</label>
          <?php if ($isOwner): ?>
            <input type="text" id="profileName" />
          <?php else: ?>
            <span id="profileName"></span>
          <?php endif; ?>
        </div>
        <div class="profileField">
          <label>Bio:</label>
          <?php if ($isOwner): ?>
            <textarea id="profileBio"></textarea>
          <?php else: ?>
            <span id="profileBio" style="white-space: pre-wrap;"></span>
          <?php endif; ?>
        </div>
        <div class="profileField">
          <label>Role:</label>
          <?php if ($isOwner): ?>
            <select id="profileRole">
              <option value="student">Student</option>
              <option value="coach">Coach</option>
              <option value="judge">Judge</option>
            </select>
          <?php else: ?>
            <span id="profileRole"></span>
          <?php endif; ?>
        </div>
        <?php if ($isOwner): ?>
          <button id="saveProfileBtn">Save Changes</button>
        <?php endif; ?>
      </div>
    </div>
    <br><br>
<div id="contentRow" style="display: flex; gap: 20px; align-items: flex-start;">
  <div id="filterSidebar">
    <div id="filterHeader">
      <strong id="activeFilters">Filters: None</strong> <a href="#" id="clearFilters">(Clear)</a>
    </div>

    <div class="filterSection">
      <h4>Sort</h4>
      <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
        <input type="radio" name="sortOption" value="title" checked />
        <label for="sort-title">Title (A–Z)</label>
      </div>
      <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
        <input type="radio" name="sortOption" value="recent" />
        <label for="sort-recent">Recent</label>
      </div>
    </div>

    <div class="filterSection">
      <h4>Filters</h4>
      <div id="categoryFilters"></div>
    </div>
  </div>

  <div style="flex: 1;">
    <div id="menuView"></div>
  </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", async () => {
    const profileId = <?php echo json_encode($profileId); ?>;
    const isOwner   = <?php echo json_encode($isOwner); ?>;
    const isGuest   = <?php echo json_encode($guestMode); ?>;

    const pic       = document.getElementById("profilePic");
    const nameEl    = document.getElementById("profileName");
    const bioEl     = document.getElementById("profileBio");
    const roleEl    = document.getElementById("profileRole");
    const saveBtn   = document.getElementById("saveProfileBtn");
    const uploadInp = document.getElementById("uploadAvatar");
        // === Guest-mode speech storage ===
const GUEST_STORAGE_KEY = "speechdeb_guest_speeches";

function getGuestSpeeches() {
  try {
    const raw = localStorage.getItem(GUEST_STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (e) {
    console.warn("Failed to parse guest speeches from localStorage:", e);
    return [];
  }
}

function setGuestSpeeches(list) {
  try {
    localStorage.setItem(GUEST_STORAGE_KEY, JSON.stringify(list || []));
  } catch (e) {
    console.warn("Failed to save guest speeches to localStorage:", e);
  }
}
        
function ensureGuestNewSpeechButton() {
  let btn = document.getElementById("newSpeechBtn");
  const menuContainer = document.getElementById("menuView");

  if (!menuContainer) {
    console.warn("Guest menu: #menuView not found.");
    return;
  }

  if (!btn) {
    btn = document.createElement("button");
    btn.id = "newSpeechBtn";
    btn.textContent = "New Speech";
    btn.className = "primaryButton"; // reuse your existing button styling

    // Insert the button just above the menu list
    if (menuContainer.parentNode) {
      menuContainer.parentNode.insertBefore(btn, menuContainer);
    } else {
      // fallback: prepend inside menuView
      menuContainer.prepend(btn);
    }
  }

  // 🔹 Center the button within the menu column
  btn.style.display = "block";
  btn.style.margin = "0 auto 15px auto";
  btn.style.textAlign = "center";

  btn.onclick = () => {
    const speeches = getGuestSpeeches();
    const newId = Date.now();

    const newSpeech = {
      id: newId,
      title: "Untitled Speech",
      category: "Uncategorized",
      updated_at: new Date().toISOString()
    };

    speeches.push(newSpeech);
    setGuestSpeeches(speeches);

    try {
      localStorage.setItem(
        "speechdeb_editor_payload",
        JSON.stringify({
          id: newId,
          title: newSpeech.title,
          category: newSpeech.category,
          content: "",
          guest: true
        })
      );
    } catch (e) {
      console.warn("Failed to set editor payload for guest:", e);
    }

    window.location.href = "editor.html?guest=1";
  };
}
        
function loadGuestSpeeches() {
  const menu = document.getElementById("menuView");
  if (!menu) {
    console.warn("Guest menu: #menuView not found.");
    return;
  }

  // Clear current menu
  menu.innerHTML = "";

  // Always ensure New Speech button exists & is wired for guest mode
  ensureGuestNewSpeechButton();

  const speeches = getGuestSpeeches();

  if (!speeches.length) {
    const emptyMsg = document.createElement("div");
    emptyMsg.id = "noSpeechesMessage";
    emptyMsg.textContent = "No speeches yet in Guest Mode. Click “New Speech” to get started.";
    emptyMsg.style = "margin-top: 20px; font-size: 20px; color: #666; text-align: center;";
    menu.appendChild(emptyMsg);
    return;
  }

  // Sort newest first (same behavior as before)
  speeches
    .sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))
    .forEach(s => {
      const box = document.createElement("div");
      box.className = "speechBox";

      const lastUpdated = s.updated_at || new Date().toISOString();
      const category = s.category || "Uncategorized";
      const title = s.title || "Untitled Speech";

      // Match logged-in inline style
      box.setAttribute("data-category", category);
      box.setAttribute("data-title", title);
      box.setAttribute("data-updated", lastUpdated);
      box.style = `
        background-color: #f9f9f9;
        border: 1px solid #ccc;
        border-radius: 10px;
        padding: 14px 20px;
        margin: 10px auto;
        max-width: 70%;
        cursor: pointer;
      `;

      // Use the same "[Category] Title" + "Last edited X ago" layout
      const relative = (typeof formatRelativeTime === "function")
        ? formatRelativeTime(lastUpdated)
        : new Date(lastUpdated).toLocaleString();

      box.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <div style="font-weight: bold; font-size: 18px;">[${category}] ${title}</div>
            <div style="color: #666; font-size: 14px;">
              Last edited ${relative}
            </div>
          </div>
          <button class="deleteBtn"
                  style="margin-left: 20px; background-color: #cc0000; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">
            Delete
          </button>
        </div>
      `;

      // Delete for guest mode: remove from localStorage + re-render
      const deleteBtn = box.querySelector(".deleteBtn");
      if (deleteBtn) {
        deleteBtn.onclick = (e) => {
          e.stopPropagation();
          if (!confirm(`Delete your ${category} speech “${title}”? This cannot be undone.`)) return;

          const all = getGuestSpeeches();
          const remaining = all.filter(sp => sp.id !== s.id);
          setGuestSpeeches(remaining);
          loadGuestSpeeches();
        };
      }

      // Clicking box → open in editor.html?guest=1 (same behavior as before)
      box.onclick = () => {
        try {
          localStorage.setItem(
            "speechdeb_editor_payload",
            JSON.stringify({
              id: s.id,
              title: s.title || "",
              category: s.category || "Uncategorized",
              content: s.content || "",
              guest: true
            })
          );
        } catch (e) {
          console.warn("Failed to set editor payload for guest:", e);
        }

        window.location.href = "editor.html?guest=1";
      };

      menu.appendChild(box);
    });
}
        
    // 🔹 GUEST MODE: no DB, just static "Guest" profile + default pic
    if (isGuest) {
      // --- Big profile box ---
      if (pic) {
        pic.src = "favicon.png";
        pic.style.cursor = "default";
      }

      if (nameEl) {
        if (nameEl.tagName === "INPUT") {
          nameEl.value = "Guest";
          nameEl.readOnly = true;
        } else {
          nameEl.textContent = "Guest";
        }
      }

      if (bioEl) {
        const guestBio = "Browse and edit speeches in Guest mode (stored locally on this device).";
        if (bioEl.tagName === "TEXTAREA") {
          bioEl.value = guestBio;
          bioEl.readOnly = true;
        } else {
          bioEl.textContent = guestBio;
        }
      }

      if (roleEl) {
        if (roleEl.tagName === "SELECT") {
          roleEl.value = "student";
          roleEl.disabled = true;
        } else {
          roleEl.textContent = "Guest";
        }
      }

      if (saveBtn)   saveBtn.style.display = "none";
      if (uploadInp) uploadInp.style.display = "none";

      // --- 🔹 TOP-RIGHT NAVBAR: show Guest + default pic ---
      const bannerPic  = document.getElementById("userAvatarDisplay");
      const bannerName = document.getElementById("userNameDisplay");

      if (bannerPic) {
        bannerPic.src = "favicon.png";
      }
      if (bannerName) {
        bannerName.textContent = "Guest";
      }

      // --- 🔹 Load guest speeches / show New Speech in guest mode ---
            // 🔹 Guest mode: use local-only guest menu loader
      if (typeof loadGuestSpeeches === "function") {
        try {
          loadGuestSpeeches();
        } catch (e) {
          console.warn("loadGuestSpeeches guest call failed:", e);
        }
      }

      return; // ⬅️ Do NOT hit the DB API below
    }

    // 🔹 NORMAL (LOGGED-IN) PROFILE FLOW
    try {
      const res = await fetch(
        `user.php?action=get&id=${encodeURIComponent(profileId)}`
      );
      const contentType = res.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        throw new Error("Non-JSON response: " + (await res.text()));
      }

      const data = await res.json();

      if (pic) {
        pic.src = data.profile_picture_url
          ? (data.profile_picture_url.startsWith("http")
              ? data.profile_picture_url
              : location.origin + "/" + data.profile_picture_url)
          : "favicon.png";
      }

      if (isOwner) {
        document.getElementById("profileName").value = data.name || "";
        document.getElementById("profileBio").value  = data.bio || "";
        document.getElementById("profileRole").value = data.role || "student";

        pic.addEventListener("click", () =>
          document.getElementById("uploadAvatar").click()
        );

        let uploadedPicUrl = null;

        document
          .getElementById("uploadAvatar")
          .addEventListener("change", async (e) => {
            const file = e.target.files[0];
            if (!file || !file.type.startsWith("image/")) return;

            const formData = new FormData();
            formData.append("id", profileId);
            formData.append("avatar", file);

            const upload = await fetch("user.php", { method: "POST", body: formData });
            const result = await upload.json();

            if (result.success && result.url) {
              uploadedPicUrl = result.url;
              pic.src = location.origin + "/" + result.url + "?t=" + Date.now();

              const bannerPic = document.getElementById("userAvatarDisplay");
              if (bannerPic) {
                bannerPic.src = location.origin + "/" + result.url + "?t=" + Date.now();
              }
            }
          });

        document
          .getElementById("saveProfileBtn")
          .addEventListener("click", async () => {
            const name = document.getElementById("profileName").value.trim();
            const bio  = document.getElementById("profileBio").value.trim();
            const role = document.getElementById("profileRole").value;

            const response = await fetch("user.php?action=update", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: new URLSearchParams({
                email: data.email,
                name,
                bio,
                role,
                profile_picture_url:
                  uploadedPicUrl || data.profile_picture_url || ""
              }),
            });

            const result = await response.json();
            if (result.success) {
              location.reload();
            } else {
              alert(result.error || "❌ Update failed.");
            }
          });
      } else {
        document.getElementById("profileName").textContent = data.name || "(Unnamed)";
        document.getElementById("profileBio").textContent  = data.bio || "";
        document.getElementById("profileRole").textContent = data.role || "";
        if (pic) pic.style.cursor = "default";
      }

      if (typeof loadSpeechesFromServer === "function") {
        loadSpeechesFromServer(profileId, isOwner);
      }
    } catch (err) {
      console.error("Profile load error:", err);
      document.getElementById("profileBox").innerHTML =
        `<p>Failed to load profile.</p><pre>${err.message}</pre>`;
    }
  });
</script>
  <script src="common.js" defer></script>
</body>
</html>
