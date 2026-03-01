<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_POST['id']) && isset($_POST['email'])) {
    $_SESSION['user_id'] = (int) $_POST['id'];
    echo json_encode(["success" => true, "id" => $_SESSION['user_id']]);
} else {
    echo json_encode(["error" => "Missing data"]);
}
?>