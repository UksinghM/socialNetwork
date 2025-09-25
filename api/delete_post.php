<?php
session_start();
require_once "../config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);

if (!$post_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid post ID.']);
    exit;
}

$stmt = $db->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
if ($stmt->execute([$post_id, $user_id]) && $stmt->rowCount() > 0) {
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete post or permission denied.']);
}