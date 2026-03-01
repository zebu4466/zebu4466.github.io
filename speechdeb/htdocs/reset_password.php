<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$conn = new mysqli('sql109.infinityfree.com', 'if0_38847172', 'Zeboosta4466', 'if0_38847172_speechdeb_users');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get inputs
$email = trim($_POST['email'] ?? '');
$code = trim($_POST['code'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');

if (empty($email) || empty($code) || empty($new_password)) {
    die("All fields are required.");
}

// Check if email + code are correct
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_code = ? AND reset_code_expires > NOW()");
$stmt->bind_param('ss', $email, $code);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die("The code you entered is expired or invalid. Please try again.");
}
$stmt->close();

// Update password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_code_expires = NULL WHERE email = ?");
$stmt->bind_param('ss', $hashed_password, $email);

if ($stmt->execute()) {
    echo "Password reset successful!";
} else {
    echo "Failed to reset password. Please try again.";
}

$stmt->close();
$conn->close();
?>