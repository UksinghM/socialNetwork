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
$description = trim($_POST['description'] ?? '');
$image_file = $_FILES['post_img'] ?? null;
$image_name = null;

if ($description === '' && !$image_file) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Post cannot be empty.']);
    exit;
}

if ($image_file && $image_file['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../uploads/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($image_file['type'], $allowed_types)) {
        $image_name = time() . '_' . basename($image_file['name']);
        if (!move_uploaded_file($image_file['tmp_name'], $upload_dir . $image_name)) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to upload image.']);
            exit;
        }
    }
}

$stmt = $db->prepare("INSERT INTO posts (user_id, description, image) VALUES (?, ?, ?)");
if ($stmt->execute([$user_id, $description, $image_name])) {
    echo json_encode(['status' => 'success', 'message' => 'Post created successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to create post.']);
}