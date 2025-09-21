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
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: #fff;
            color: #333;
            padding: 0 20px;
            border-bottom: 1px solid #ddd;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar a {
            color: #007bff;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
            font-size: 16px;
        }
        .navbar .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            text-decoration: none;
        }
        .container {
            width: 100%;
            max-width: 680px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .post-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .post-header img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            margin-right: 15px;
        }
        .post-header strong {
            font-size: 16px;
        }
        .post-content p {
            font-size: 15px;
            line-height: 1.5;
            margin: 0 0 15px 0;
        }
        .post-content img {
            max-width: 100%;
            border-radius: 10px;
            margin-top: 10px;
        }
        .post-footer {
            color: #65676b;
            font-size: 13px;
        }
        .post-actions {
            display: flex;
            align-items: center;
            gap: 15px;
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-top: 15px;
        }
        .post-actions button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            color: #65676b;
            padding: 0;
        }
        .post-actions button:hover {
            text-decoration: underline;
        }
        .post-actions span {
            font-size: 14px;
            color: #65676b;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="feed.php" class="logo">SocialNet</a>
        <div>
            <a href="profile.php">My Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-header">
                    <img src="/social_network/uploads/<?= htmlspecialchars($post["profile_pic"]) ?>" alt="Profile Picture">
                    <strong><?= htmlspecialchars($post["full_name"]) ?></strong>
                </div>
                <div class="post-content">
                    <p><?= htmlspecialchars($post["description"]) ?></p>
                    <?php if ($post["image"]): ?>
                        <img src="/social_network/uploads/<?= htmlspecialchars($post["image"]) ?>" alt="Post Image">
                    <?php endif; ?>
                </div>
                <div class="post-footer">
                    <small>Posted on <?= date("F j, Y, g:i a", strtotime($post["created_at"])) ?></small>
                </div>
                <div class="post-actions">
                    <button class="like-btn" data-post="<?= $post["id"] ?>">👍 Like</button>
                    <span id="likes-<?= $post["id"] ?>"></span>
                    <button class="dislike-btn" data-post="<?= $post["id"] ?>">👎 Dislike</button>
                    <span id="dislikes-<?= $post["id"] ?>"></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

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
