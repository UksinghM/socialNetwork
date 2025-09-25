<?php
session_start();
require_once "../config/db.php";
require_once "../classes/Comment.php";

header('Content-Type: application/json');

// 🔒 Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$commentObj = new Comment($db);

$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$text = trim($_POST['text'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$post_id || $text === '') {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// ✅ Add comment
if ($commentObj->add($post_id, $user_id, $text)) {
    // Return the new comment data so we can show it without reload
    echo json_encode([
        'status' => 'success',
        'comment' => [
            'full_name' => $_SESSION['name'] ?? 'You',
            'comment' => htmlspecialchars($text)
        ]
    ]);
} else {
    echo json_encode(['status' => 'error']);
}
