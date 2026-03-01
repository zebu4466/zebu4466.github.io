<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🔐 Validate input
$email = $_POST['email'] ?? '';
if (!$email) {
    http_response_code(400);
    echo json_encode(["error" => "❌ Missing email"]);
    exit();
}

// 🔌 DB credentials
$servername = "sql109.infinityfree.com";
$username = "if0_38847172";
$password = "Zeboosta4466";
$dbname = "if0_38847172_speechdeb_users";

// 🔌 DB connect
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "❌ DB connection failed"]);
    exit();
}

// 🔍 Determine viewer ID from session
// 🔍 Get user ID by email
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userId);
if (!$stmt->fetch()) {
    $stmt->close();
    echo json_encode(["error" => "❌ Email not found"]);
    exit();
}
$stmt->close();

session_start();
$viewerId = $_SESSION['user_id'] ?? null;
$isOwner = ($viewerId && (int)$viewerId === (int)$userId);

// 🔐 Construct secure query
if ($isOwner) {
    // ✅ Owner can see all their speeches
    $sql = "SELECT s.id, s.title, s.content, s.category, s.memorization_mode, s.done_sentences,
                   s.created_at, s.updated_at, s.share_status, u.email AS owner_email,
                   1 AS is_owner
            FROM speeches s
            JOIN users u ON s.user_id = u.id
            WHERE s.user_id = ?
            ORDER BY s.updated_at DESC, s.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
} else {
    // ❗ Viewer is someone else — only return public/shared
    $sql = "SELECT s.id, s.title, s.content, s.category, s.memorization_mode, s.done_sentences,
                   s.created_at, s.updated_at, s.share_status, u.email AS owner_email,
                   0 AS is_owner
            FROM speeches s
            JOIN users u ON s.user_id = u.id
            WHERE s.user_id = ? AND s.share_status IN ('public', 'shared')
            ORDER BY s.updated_at DESC, s.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$result = $stmt->get_result();

// 🔄 Format results
$speeches = [];
while ($row = $result->fetch_assoc()) {
    $row['memorization_mode'] = (int) $row['memorization_mode'];
    $row['done_sentences'] = json_decode($row['done_sentences'], true) ?? [];
    $row['share_status'] = $row['share_status'] ?? 'private';
    $row['owner_email'] = $row['owner_email'] ?? 'unknown';
    $row['is_owner'] = (int) $row['is_owner'];
    $speeches[] = $row;
}

$stmt->close();
$conn->close();

echo json_encode($speeches);