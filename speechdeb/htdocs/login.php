<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "sql109.infinityfree.com";
$username = "if0_38847172";
$password = "Zeboosta4466";
$dbname = "if0_38847172_speechdeb_users";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die("❌ Database connection failed: " . $e->getMessage());
}

// === POST: Handle login logic ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");

    $email = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

        if (!$email || !$passwordInput) {
        http_response_code(400);
        echo json_encode(["error" => "Please enter both email and password."]);
        exit();
    }

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $hashedPassword);
        $stmt->fetch();

        if (password_verify($passwordInput, $hashedPassword)) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['email'] = $email;
            echo json_encode(["success" => true]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Wrong password, please try again."]);
        }
    } else {
        http_response_code(404);
        echo json_encode([
            "error" => "We couldn't find an account with that email. Try signing up."
        ]);
    }

    exit();
}
?>

<?php
$loginError = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'user-not-found':
            $loginError = "We couldn't find an account with that email. Try signing up.";
            break;
        case 'wrong-password':
            $loginError = "Wrong password, please try again.";
            break;
        case 'no-email':
            $loginError = "Please enter your email.";
            break;
        case 'db':
            $loginError = "We had a problem connecting. Please try again in a moment.";
            break;
        default:
            $loginError = "Something went wrong. Please try again.";
            break;
    }
}
?>

<!-- === GET: Show login page === -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login</title>
  <link rel="stylesheet" href="styles.css" />
  <script type="module" src="common.js"></script>
</head>
<body>
  <main>
    <div id="authBox" class="authContainer">
      <h2 id="formHeading">Login</h2>
      <form id="loginForm">
        <input type="email" id="loginEmail" placeholder="Email" required />
        <input type="password" id="loginPassword" placeholder="Password" required />
        <button type="submit">Login</button>
        <p id="loginError" style="color:#c00; margin-top:8px;">
          <?php if (!empty($loginError)) echo htmlspecialchars($loginError); ?>
        </p>
        <p><a href="reset.html">Forgot Password?</a></p>
      </form>
      <p>Don't have an account? <a href="signup.php">Sign up</a></p>
      <div style="margin-top:8px; text-align:center;">
        <a href="#" id="stayLoggedOutBtn" style="color: #007ACC;">
          Stay logged out
        </a>
        <span
  id="stayLoggedOutInfo"
  style="color:#007ACC"
  onclick="window.location.href='./blog/2025/12/29/guest-mode.php'"
>
          (learn more)
        </span>
      </div>
    </div>
  </main>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const loginForm = document.getElementById("loginForm");
      const loginErrorEl = document.getElementById("loginError");

      if (loginForm) {
        loginForm.addEventListener("submit", async (e) => {
          e.preventDefault();
          const email = document.getElementById("loginEmail").value.trim();
          const password = document.getElementById("loginPassword").value.trim();

          let res;
          try {
            res = await fetch("login.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              credentials: "include",
              body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
            });
          } catch (err) {
            if (loginErrorEl) {
              loginErrorEl.textContent = "Network error. Please check your connection and try again.";
            }
            return;
          }

          if (!res.ok) {
            let msg = "Something went wrong. Please try again.";
            try {
              const err = await res.json();
              if (err && err.error) msg = err.error;
            } catch (e) {
              // If response isn't valid JSON, we still show a generic message
            }

            if (loginErrorEl) {
              loginErrorEl.textContent = msg;
              loginErrorEl.style.display = "block";
              loginErrorEl.style.color = "#c00";
            }
          } else {
            // ✅ Normal logged-in flow
            localStorage.setItem("speechdeb_loggedin", "true");
            localStorage.setItem("speechdeb_email", email);
            localStorage.removeItem("speechdeb_guest"); // no longer a guest
            window.location.href = "index.php";
          }
        });
      }

      // ✅ Guest / "Stay logged out" mode
      const stayBtn = document.getElementById("stayLoggedOutBtn");
      if (stayBtn) {
        stayBtn.addEventListener("click", function (e) {
          e.preventDefault(); // it's a link

          // Guest mode: keep speeches local only
          localStorage.setItem("speechdeb_guest", "1");
          localStorage.removeItem("speechdeb_email");
          localStorage.removeItem("speechdeb_loggedin");

          // Go through index.php in guest mode
          window.location.href = "index.php?guest=1";
        });
      }

      // ℹ️ tooltip works via title="" attribute; no JS needed here
    });
  </script>
</body>
</html>