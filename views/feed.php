<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Fetch all posts with user details
$stmt = $db->prepare("
    SELECT posts.*, users.full_name, users.profile_pic 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>News Feed</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>News Feed</h2>
    <a href="profile.php">My Profile</a> | 
    <a href="logout.php">Logout</a>
    <hr>

    <?php foreach ($posts as $post): ?>
        <div style="border:1px solid #ccc; padding:10px; margin:10px 0;">
            <p><strong><?= htmlspecialchars($post["full_name"]) ?></strong></p>
            <img src="../uploads/<?= htmlspecialchars($post["profile_pic"]) ?>" width="50" style="border-radius:50%;"><br>
            <p><?= htmlspecialchars($post["description"]) ?></p>
            <?php if ($post["image"]): ?>
                <img src="../uploads/<?= htmlspecialchars($post["image"]) ?>" width="200"><br>
            <?php endif; ?>
            <small>Posted on <?= $post["created_at"] ?></small><br>

            <!-- Like / Dislike buttons -->
            <button class="like-btn" data-post="<?= $post["id"] ?>">👍 Like</button>
            <button class="dislike-btn" data-post="<?= $post["id"] ?>">👎 Dislike</button>
            <span id="likes-<?= $post["id"] ?>"></span>
            <span id="dislikes-<?= $post["id"] ?>"></span>
        </div>
    <?php endforeach; ?>

    <script>
    $(document).ready(function(){
        $(".like-btn, .dislike-btn").click(function(){
            let post_id = $(this).data("post");
            let type = $(this).hasClass("like-btn") ? "like" : "dislike";

            $.post("like_post.php", { post_id: post_id, type: type }, function(data){
                let res = JSON.parse(data);
                $("#likes-" + post_id).text(" Likes: " + res.likes);
                $("#dislikes-" + post_id).text(" Dislikes: " + res.dislikes);
            });
        });
    });
    </script>
</body>
</html>
