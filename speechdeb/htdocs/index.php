<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

        if (!$email) {
        header("Location: login.php?error=no-email");
        exit;
    }

    // DB config
    $servername = "sql109.infinityfree.com";
    $username   = "if0_38847172";
    $password   = "Zeboosta4466";
    $dbname     = "if0_38847172_speechdeb_users";

    $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
        header("Location: login.php?error=db");
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($userId);

        if ($stmt->fetch()) {
        $stmt->close();
        $conn->close();
        header("Location: user.php?id=" . urlencode($userId));
        exit;
    } else {
        $stmt->close();
        $conn->close();
        http_response_code(404); // still "gives 404"
        header("Location: login.php?error=user-not-found");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Redirecting...</title>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const params = new URLSearchParams(window.location.search);
      const guestFromUrl = params.get("guest") === "1";

      let guestFromStorage = false;
      let email = null;

      try {
        guestFromStorage = localStorage.getItem("speechdeb_guest") === "1";
        email = localStorage.getItem("speechdeb_email");
      } catch (e) {
        // localStorage might not be available (very rare), just ignore
      }

      const guestMode = guestFromUrl || guestFromStorage;

      // 1) Guest mode: skip login, go straight to editor
      if (guestMode) {
        try {
          localStorage.setItem("speechdeb_guest", "1");
        } catch (e) {}
        window.location.href = "editor.html"; // change if filename differs
        return;
      }

      // 2) Normal auto-login via stored email
      if (email) {
        const form = document.createElement("form");
        form.method = "POST";
        form.style.display = "none";

        const input = document.createElement("input");
        input.name = "email";
        input.value = email;
        form.appendChild(input);

        document.body.appendChild(form);
        form.submit();
      } else {
        // 3) No email + not guest -> go to login screen
        window.location.href = "login.php";
      }
    });
  </script>
</head>
<body>
</body>
</html>