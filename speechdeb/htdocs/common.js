  const APP_VERSION = "v1.02";
  const CURRENT_YEAR = new Date().getFullYear();

  const html = `
    <!-- ✅ VERSION BANNER -->
    <div id="versionBannerMenu">
      <div id="menuHeading">
        <a style="color: white;" href="https://speechdeb.infy.uk/index.php">Speechdeb Editor <span id="menuVersion"></span></a>
      </div>
      <br>
      <div id="subtitle">
        <a style="color: #bbbbbb;" href="https://speechdeb.infy.uk/index.php">The first text editor for the National Speech & Debate Association!</a>
      </div>
    </div>

    <!-- ✅ USER PANEL -->
    <div id="userPanel" style="display: none;">
      <div id="toggleUserMenu">
<img id="userAvatarDisplay" src="" alt="Profile" style="width: 28px; height: 28px; object-fit: cover; margin-right: 8px; vertical-align: middle; border-radius: 4px;">
<span id="userEmailDisplay"></span>
        <span id="userDropdownArrow">▼</span>
      </div>
      <div id="userMenuItems">
        <div id="profile">Speeches</div>
        <div id="prefs">Preferences</div>
        <div id="resetPassword">Reset Password</div>
        <div id="deleteAccount">Delete Account</div>
        <div id="support">Support</div>
        <div id="logout">Logout</div>
      </div>
    </div>

        <!-- Alert Bar -->
    <div id="globalAlert"></div>

    <!-- Custom Alert -->
    <div id="customAlertBox" style="display: none;">
      <div class="customAlertHeader">
        <h3 id="alertBoxHeading">Alert</h3>
        <button class="closeBtn" onclick="closeCustomAlert()">✖</button>
      </div>
      <hr />
      <p id="alertBoxMessage" style="margin: 12px 0;"></p>
      <div class="customAlertButtons">
        <button id="alertBoxCancelBtn">Cancel</button>
        <button id="alertBoxOkBtn">OK</button>
      </div>
    </div>
  </div>

    <!-- ✅ FOOTER -->
    <div id="footer">
    <hr>
      Speechdeb Editor <span id="footerVersion"></span> ® 2025-${CURRENT_YEAR} The Speechdeb Team •
      <a href="contact.html">Contact Support</a> •
      <a href="https://speechdeb.infy.uk/blog/blog.php">Blog</a>
      <hr>
      <p>
        The Speechdeb Team is not affiliated with the <a href="https://mmssl.weebly.com">MMSSL</a>,
        the <a href="https://msdlonline.org">MSDL</a>, the <a href="https://bostondebate.org">BDL</a>, the <a href="https://speechanddebate.org">NSDA</a>, or any such organization.
      </p>
    </div>
  `;

  // Wait for DOM to load then inject components
// Wait for DOM to load then inject components
window.addEventListener("DOMContentLoaded", () => {
  const wrapper = document.createElement("div");
  wrapper.innerHTML = html;

  const banner = wrapper.querySelector("#versionBannerMenu");
  const userPanel = wrapper.querySelector("#userPanel");
  const footer = wrapper.querySelector("#footer");
  const alertBar = wrapper.querySelector("#globalAlert");
  const customAlert = wrapper.querySelector("#customAlertBox");

  if (banner) document.body.prepend(banner);
  if (userPanel) document.body.prepend(userPanel);
  if (alertBar) document.body.appendChild(alertBar);
  if (customAlert) document.body.appendChild(customAlert);
  if (footer) document.body.appendChild(footer);

  // Apply version text
  document.querySelectorAll("#menuVersion, #footerVersion").forEach(el => {
    el.textContent = APP_VERSION;
  });

  // 🔹 Figure out guest mode from URL *or* localStorage
  const urlParams    = new URLSearchParams(window.location.search);
  const guestFromUrl = urlParams.get("guest") === "1";

  if (guestFromUrl) {
    // Sticky guest mode once you've entered via ?guest=1
    localStorage.setItem("speechdeb_guest", "1");
  }

  const loggedIn  = localStorage.getItem("speechdeb_loggedin");
  const email     = localStorage.getItem("speechdeb_email");
  const guestMode = guestFromUrl || localStorage.getItem("speechdeb_guest") === "1";

  // 🔹 USER PANEL: Guest first, then logged-in user
  const userPanelEl   = document.getElementById("userPanel");
  const emailDisplay  = document.getElementById("userEmailDisplay");
  const avatarImg     = document.getElementById("userAvatarDisplay");

  if (guestMode) {
    // ✅ Guest navbar: default pic + "Guest"
    if (userPanelEl && emailDisplay) {
      userPanelEl.style.display = "block";
      emailDisplay.textContent  = "Guest";
    }
    if (avatarImg) {
      avatarImg.src = "favicon.png";
    }
  } else if (loggedIn === "true" && email) {
    // ✅ Normal logged-in user navbar
    fetch("user.php?action=get&email=" + encodeURIComponent(email))
      .then(res => res.json())
      .then(data => {
        const nameToShow = data.name || email; // fallback to email
        if (userPanelEl && emailDisplay) {
          userPanelEl.style.display = "block";
          emailDisplay.textContent  = nameToShow;
        }

        if (avatarImg) {
          const src = data.profile_picture_url 
            ? (data.profile_picture_url.startsWith("http")
                ? data.profile_picture_url
                : location.origin + "/" + data.profile_picture_url)
            : "favicon.png";
          avatarImg.src = src + "?t=" + Date.now(); // 🧼 cache-bust
        }
      })
      .catch(err => {
        console.error("❌ Failed to fetch user profile:", err);
        if (userPanelEl && emailDisplay) {
          userPanelEl.style.display = "block";
          emailDisplay.textContent  = email; // fallback if fetch fails
        }
      });
  } else {
    // ❌ Not guest and not logged in → navbar stays hidden
    if (userPanelEl) {
      userPanelEl.style.display = "none";
    }
  }

  // ✅ Click handlers for navbar items
  const prefs      = document.getElementById("prefs");
  const reset      = document.getElementById("resetPassword");
  const del        = document.getElementById("deleteAccount");
  const support    = document.getElementById("support");
  const logoutBtn  = document.getElementById("logout");

  // 🔹 Guest mode: hide Reset Password & Delete Account
  if (guestMode) {
    if (reset) reset.style.display = "none";
    if (del)   del.style.display   = "none";
  } else if (loggedIn === "true" && email) {
    // 🔹 Logged-in: wire up reset + delete
    if (reset) {
      reset.onclick = () => window.location.href = "reset.html";
    }
    if (del) {
      del.onclick = () => {
        if (typeof window.confirmAccountDeletion === "function") {
          window.confirmAccountDeletion();
        }
      };
    }
  }

  // These are fine for both guest + logged-in
  if (prefs)   prefs.onclick   = () => window.location.href = "https://speechdeb.infy.uk/settings.html";
  if (support) support.onclick = () => window.location.href = "http://speechdeb.infy.uk/contact.html";

  // 🔹 Logout clears guest + login flags and ALWAYS goes to login screen
  function logout() {
    localStorage.removeItem("speechdeb_loggedin");
    localStorage.removeItem("speechdeb_email");
    localStorage.removeItem("speechdeb_guest");
    window.location.href = "login.php";  // ⬅️ important change
  }
  window.logout = logout;
  if (logoutBtn) logoutBtn.onclick = logout;

  // ✅ Dropdown toggle
  const toggle = document.getElementById("toggleUserMenu");
  const arrow  = document.getElementById("userDropdownArrow");
  const menu   = document.getElementById("userMenuItems");

  if (toggle && arrow && menu) {
    toggle.addEventListener("click", () => {
      const visible = menu.classList.contains("visible");
      menu.classList.toggle("visible", !visible);
      arrow.textContent = visible ? "▼" : "▲";
    });
  }

  // ✅ Make toggleUserMenu accessible globally
  window.toggleUserMenu = () => {
    const menu  = document.getElementById("userMenuItems");
    const arrow = document.getElementById("userDropdownArrow");
    if (!menu || !arrow) return;
    const isVisible = menu.classList.contains("visible");
    menu.classList.toggle("visible", !isVisible);
    arrow.textContent = isVisible ? "▼" : "▲";
  };

  // ✅ Small-screen overlay
  function checkScreenSize() {
    const minWidth = 810;
    const overlayId = "screenTooSmallOverlay";
    let overlay = document.getElementById(overlayId);

    if (window.innerWidth < minWidth) {
      if (!overlay) {
        overlay = document.createElement("div");
        overlay.id = overlayId;
        overlay.style.position = "fixed";
        overlay.style.top = "0";
        overlay.style.left = "0";
        overlay.style.width = "100vw";
        overlay.style.height = "100vh";
        overlay.style.backgroundColor = "rgba(255, 255, 255, 1)";
        overlay.style.zIndex = "99999";
        overlay.style.display = "flex";
        overlay.style.flexDirection = "column";
        overlay.style.alignItems = "center";
        overlay.style.justifyContent = "center";
        overlay.style.fontFamily = "inherit";
        overlay.style.fontSize = "24px";
        overlay.style.color = "#333";
        overlay.style.textAlign = "center";
        overlay.innerHTML = `
          <div>
            <p style="font-size: 28px; font-weight: bold;">Screen too small</p>
            <p style="margin-top: 12px;">Your display is too small.</p>
          </div>
        `;
        document.body.appendChild(overlay);
      }
    } else {
      if (overlay) overlay.remove();
    }
  }

  checkScreenSize();
  window.addEventListener("resize", checkScreenSize);
});

// ✅ Make it accessible to inline HTML

  // Inject styles.css if not already present
  if (!document.querySelector('link[href="styles.css"]')) {
    const styleLink = document.createElement("link");
    styleLink.rel = "stylesheet";
    styleLink.href = "styles.css";
    document.head.appendChild(styleLink);
  }

// Inject script.js if not already present
function loadScriptAndInit() {
  const script = document.createElement("script");
  script.src = "script.js";
  script.onload = () => {
    if (typeof initScript === "function") {
      initScript();
    }
  };
  document.head.appendChild(script);
}

// ✅ Only load script.js on editor/menu pages, NOT on pure auth / public pages
document.addEventListener("DOMContentLoaded", () => {
  const path = window.location.pathname;

  const isNonEditorPage =
    path.includes("login.php")   ||
    path.includes("signup.php")  ||
    path.includes("reset.html")  ||
    path.includes("contact.html") ||
    path.includes("/blog/") ||
    path.includes("404.html");

  if (isNonEditorPage) {
    // These pages use their own inline JS
    return;
  }

  loadScriptAndInit();
});