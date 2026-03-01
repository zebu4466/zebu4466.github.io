<?php
ob_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === DATABASE CONFIG ===
$servername = "sql109.infinityfree.com";
$username   = "if0_38847172";
$password   = "Zeboosta4466";
$dbname     = "if0_38847172_speechdeb_users";

// === INPUT VALIDATION ===
$email              = trim($_POST['email'] ?? '');
$title              = trim($_POST['title'] ?? '');
$content            = trim($_POST['content'] ?? '');
$category           = trim($_POST['category'] ?? '');
$memorization_mode  = intval($_POST['memorization_mode'] ?? 0);
$done_sentences_raw = $_POST['done_sentences'] ?? '[]';
$share_status       = trim($_POST['share_status'] ?? 'private');
$id                 = isset($_POST['id']) && is_numeric($_POST['id']) ? intval($_POST['id']) : null;

if (!$email || !$title || !$content || !$category) {
    echo json_encode(["error" => "⚠️ Missing required fields."]);
    exit;
}

$done_sentences_array = json_decode($done_sentences_raw, true);
if (!is_array($done_sentences_array)) $done_sentences_array = [];
$done_sentences = json_encode($done_sentences_array, JSON_UNESCAPED_UNICODE);

// === CONNECT TO DB ===
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "❌ DB connection failed: " . $conn->connect_error]);
    exit;
}

// === GET USER ID ===
$userId = null;
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userId);
$stmt->fetch();
$stmt->close();

if (!$userId) {
    echo json_encode(["error" => "❌ No user found with email: $email"]);
    $conn->close();
    exit;
}

$success = false;

try {
    if ($id) {
        // === UPDATE ===
        $stmt = $conn->prepare("SELECT id FROM speeches WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            $conn->close();
            echo json_encode(["error" => "❌ Unauthorized: You do not own this speech."]);
            exit;
        }
        $stmt->close();

        $stmt = $conn->prepare("
            UPDATE speeches SET
                title = ?, content = ?, category = ?,
                memorization_mode = ?, done_sentences = ?, share_status = ?,
                updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("sssissii", $title, $content, $category, $memorization_mode, $done_sentences, $share_status, $id, $userId);
        $success = $stmt->execute();
        $stmt->close();
    } else {
        // === INSERT ===
        $stmt = $conn->prepare("
            INSERT INTO speeches (
                user_id, title, content, category, memorization_mode,
                done_sentences, share_status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->bind_param("isssiss", $userId, $title, $content, $category, $memorization_mode, $done_sentences, $share_status);
        $success = $stmt->execute();
        if ($success) {
            $id = $stmt->insert_id;
        }
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(["error" => "❌ You already have a speech titled “$title”. Try renaming it."]);
    } else {
        echo json_encode(["error" => "❌ DB error: " . $e->getMessage()]);
    }
    $conn->close();
    exit;
}

$conn->close();
ob_clean();

if ($success) {
    error_log("✅ Updated speech ID $id with share_status = $share_status");
    echo json_encode(["success" => true, "id" => $id]);
} else {
    echo json_encode(["error" => "❌ Save failed. Please try again."]);
}
exit;
?>