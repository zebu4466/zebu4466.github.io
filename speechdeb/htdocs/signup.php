<?php
// === Backend Processing ===
$response = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  header('Content-Type: application/json');
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
    echo json_encode(["error" => "❌ Database connection failed."]);
    exit();
  }

  $email = trim($_POST['email'] ?? '');
  $pass = trim($_POST['password'] ?? '');

  if (!$email || !$pass) {
    http_response_code(400);
    echo json_encode(["error" => "❌ Email and password are required."]);
    exit();
  }

  // Check if email already exists
  $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(["error" => "An account with that email already exists!"]);
    exit();
  }
  $stmt->close();

  $hashed = password_hash($pass, PASSWORD_DEFAULT);
  $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
  $stmt->bind_param("ss", $email, $hashed);

  if ($stmt->execute()) {
    echo json_encode([
      "success" => true,
      "email"   => $email,
      "password"=> $pass
    ]);
  } else {
    http_response_code(500);
    echo json_encode(["error" => "❌ Failed to register."]);
  }

  $stmt->close();
  $conn->close();
  exit();
}
?>

<!-- === Frontend Signup Form === -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Sign Up</title>
  <link rel="stylesheet" href="./styles.css" />
  <script type="module" src="common.js"></script>
</head>
<body>
  <main>
    <div id="authBox" class="authContainer">
      <h2 id="formHeading">Sign Up</h2>

      <form id="signupForm" onsubmit="return handleSignup(event)">
        <input
          type="email"
          id="signupEmail"
          placeholder="Email"
          required
        />

        <input
          type="password"
          id="signupPassword"
          placeholder="Password"
          required
        />

        <!-- ✅ NEW: Repeat Password field -->
        <input
          type="password"
          id="signupPasswordConfirm"
          placeholder="Repeat password"
          aria-label="Repeat password to confirm"
          required
        />

        <button type="submit">Sign Up</button>
        <p id="signupError"></p>
      </form>

      <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
  </main>

  <script>
    function handleSignup(event) {
      event.preventDefault();

      const email    = document.getElementById("signupEmail").value.trim();
      const password = document.getElementById("signupPassword").value.trim();
      const confirm  = document.getElementById("signupPasswordConfirm").value.trim();
      const errorEl  = document.getElementById("signupError");

      // ✅ Clear previous error
      if (errorEl) {
        errorEl.textContent = "";
        errorEl.style.color = "";
      }

      // ✅ Check password match on the frontend
      if (password !== confirm) {
        const msg = "Passwords do not match. Please type the same password twice.";
        if (errorEl) {
          errorEl.textContent = msg;
          errorEl.style.color = "red";
        }
        alert(msg);
        return false;
      }

      fetch("signup.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`
      })
      .then(async (res) => {
        const text = await res.text();
        let data;

        try {
          data = JSON.parse(text);
        } catch (e) {
          throw new Error("❌ Invalid server response.");
        }

        if (!res.ok) {
          // Error from backend
          const msg = data.error || "❌ Signup failed.";
          throw new Error(msg);
        }

        return data;
      })
      .then(data => {
        if (data.success) {
          // ✅ Alert for success
          alert("Successfully created an account!");

          // Store creds for auto-login on login.php
          localStorage.setItem("autoLoginEmail", data.email);
          localStorage.setItem("autoLoginPass", data.password);

          window.location.href = "login.php?autologin=true";
        } else {
          const msg = data.error || "❌ Unknown error.";
          if (errorEl) {
            errorEl.textContent = msg;
            errorEl.style.color = "red";
          }
          alert(msg);
        }
      })
      .catch(err => {
        const msg = err.message || "❌ Signup failed.";

        // ✅ Special message if email already exists
        if (msg.toLowerCase().includes("already") && msg.toLowerCase().includes("exists")) {
          alert("An account with that email already exists!");
        } else {
          alert(msg);
        }

        if (errorEl) {
          errorEl.textContent = msg;
          errorEl.style.color = "red";
        }
      });

      return false;
    }
  </script>
</body>
</html>