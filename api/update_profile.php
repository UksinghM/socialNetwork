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
$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');

$allowed_fields = ['name' => 'full_name', 'age' => 'age'];

if (!array_key_exists($field, $allowed_fields) || $value === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid field or value.']);
    exit;
}

$db_field = $allowed_fields[$field];

$stmt = $db->prepare("UPDATE users SET {$db_field} = ? WHERE id = ?");

if ($stmt->execute([$value, $user_id])) {
    if ($field === 'name') $_SESSION['name'] = $value;
    echo json_encode(['status' => 'success']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update profile.']);
}