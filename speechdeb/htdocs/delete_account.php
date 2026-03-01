<?php
header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
if (!$email) {
    echo "Missing email.";
    exit();
}

$servername = "sql109.infinityfree.com";
$username = "if0_38847172";
$password = "Zeboosta4466";
$dbname = "if0_38847172_speechdeb_users";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo "Database connection failed.";
    exit();
}

// Delete all speeches first
$stmt1 = $conn->prepare("DELETE FROM speeches WHERE user_id = (SELECT id FROM users WHERE email = ?)");
$stmt1->bind_param("s", $email);
$stmt1->execute();
$stmt1->close();

// Then delete the user
$stmt2 = $conn->prepare("DELETE FROM users WHERE email = ?");
$stmt2->bind_param("s", $email);

if ($stmt2->execute()) {
    echo "Account deleted successfully.";
} else {
    echo "Failed to delete account.";
}

$stmt2->close();
$conn->close();
?>