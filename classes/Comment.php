<?php
class Comment {
    private $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    // 🔹 Add a comment
    public function add($post_id, $user_id, $comment) {
        $stmt = $this->conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        return $stmt->execute([$post_id, $user_id, $comment]);
    }

    // 🔹 Get all comments for a post
    public function getByPost($post_id) {
        $stmt = $this->conn->prepare("
            SELECT c.id, c.comment, c.created_at, u.full_name, u.profile_pic
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ? ORDER BY c.created_at ASC
        ");
        $stmt->execute([$post_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}