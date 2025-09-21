<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION["user_id"];
$post_id = $_POST["post_id"];
$type = $_POST["type"]; // like or dislike

// Remove old reaction if any
$stmt = $db->prepare("DELETE FROM likes WHERE user_id=? AND post_id=?");
$stmt->execute([$user_id, $post_id]);

// Insert new reaction
$stmt = $db->prepare("INSERT INTO likes (post_id, user_id, type) VALUES (?, ?, ?)");
$stmt->execute([$post_id, $user_id, $type]);

// Return updated counts
$stmt = $db->prepare("SELECT 
    SUM(type='like') as likes, 
    SUM(type='dislike') as dislikes 
    FROM likes WHERE post_id=?");
$stmt->execute([$post_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result);
