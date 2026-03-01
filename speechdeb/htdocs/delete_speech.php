<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "sql109.infinityfree.com";
$username = "if0_38847172";
$password = "Zeboosta4466";
$dbname = "if0_38847172_speechdeb_users";

$email = trim($_POST['email'] ?? '');
$id = intval($_POST['id'] ?? 0);

if (!$email || !$id) {
    echo json_encode(["error" => "Missing parameters."]);
    exit();
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "DB connection failed: " . $conn->connect_error]);
    exit();
}

$stmt = $conn->prepare("DELETE FROM speeches WHERE id = ? AND user_id = (SELECT id FROM users WHERE email = ?)");
$stmt->bind_param("is", $id, $email);

if ($stmt->execute()) {
    echo json_encode(["success" => "Speech deleted successfully."]);
} else {
    echo json_encode(["error" => "Delete failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>