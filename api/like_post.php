<?php
session_start();
require_once "../config/db.php";
require_once "../classes/Like.php";

header('Content-Type: application/json');

// 🔒 Ensure user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

// ✅ Connect DB + Like class
$database = new Database();
$db = $database->getConnection();
$like = new Like($db);

$user_id = $_SESSION["user_id"];

// ✅ Validate input
$post_id = filter_input(INPUT_POST, "post_id", FILTER_VALIDATE_INT);
$type = $_POST["type"] ?? "";

if (!$post_id || !in_array($type, ["like", "dislike"])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

// ✅ Update reaction and return updated counts
$like->toggle($user_id, $post_id, $type);
echo json_encode($like->counts($post_id));