<?php
class Like {
    private $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    // 🔹 Toggle like/dislike
    public function toggle($user_id, $post_id, $type) {
        // Check for existing reaction
        $stmt = $this->conn->prepare("SELECT type FROM likes WHERE user_id=? AND post_id=?");
        $stmt->execute([$user_id, $post_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        // remove old reaction
        $stmt = $this->conn->prepare("DELETE FROM likes WHERE user_id=? AND post_id=?");
        $stmt->execute([$user_id, $post_id]);

        // If the user is not clicking the same button again, insert new reaction
        if (!$existing || $existing['type'] !== $type) {
            $stmt = $this->conn->prepare("INSERT INTO likes (post_id, user_id, type) VALUES (?, ?, ?)");
            return $stmt->execute([$post_id, $user_id, $type]);
        }
        return true; // User un-liked/un-disliked
    }

    // 🔹 Get updated counts
    public function counts($post_id) {
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(type='like'),0) AS likes, COALESCE(SUM(type='dislike'),0) AS dislikes FROM likes WHERE post_id=?");
        $stmt->execute([$post_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}