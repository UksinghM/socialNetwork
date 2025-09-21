<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION["user_id"];

// ✅ Handle new comment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_comment"])) {
    $post_id = $_POST["comment_post_id"];
    $comment_text = trim($_POST["comment_text"]);

    if (!empty($comment_text)) {
        $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $user_id, $comment_text]);
    }

    header("Location: feed.php");
    exit;
}

// ✅ Handle follow
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["follow"])) {
    $follow_user_id = $_POST["follow_user_id"];
    if ($follow_user_id != $user_id) {
        $stmt = $db->prepare("INSERT IGNORE INTO followers (user_id, follower_id) VALUES (?, ?)");
        $stmt->execute([$follow_user_id, $user_id]);
    }
    header("Location: feed.php");
    exit;
}

// ✅ Fetch all posts with user details
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
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 0; }
        .navbar { background: #fff; padding: 0 20px; border-bottom: 1px solid #ddd; height: 60px;
                  display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; }
        .navbar a { color: #007bff; text-decoration: none; margin-left: 20px; font-weight: bold; }
        .navbar .logo { font-size: 24px; font-weight: bold; color: #007bff; }

        .container { width: 100%; max-width: 680px; margin: 20px auto; padding: 0 15px; }
        .post-card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 12px;
                     box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
        .post-header { display: flex; align-items: center; margin-bottom: 15px; justify-content: space-between; }
        .post-header-left { display: flex; align-items: center; }
        .post-header img { width: 45px; height: 45px; border-radius: 50%; margin-right: 15px; }
        .post-header strong { font-size: 16px; }
        .follow-btn { background: #007bff; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .follow-btn:hover { background: #0056b3; }

        .post-content p { font-size: 15px; line-height: 1.5; margin: 0 0 15px 0; }
        .post-content img { max-width: 100%; border-radius: 10px; margin-top: 10px; }
        .post-footer { color: #65676b; font-size: 13px; }

        .post-actions { display: flex; align-items: center; gap: 15px; border-top: 1px solid #eee; padding-top: 10px; margin-top: 15px; }
        .post-actions button { background: none; border: none; cursor: pointer; font-size: 14px; font-weight: bold; color: #65676b; }
        .post-actions button:hover { text-decoration: underline; }

        .comments { margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; }
        .comment { margin-bottom: 8px; font-size: 14px; }
        .comment strong { color: #333; }
        .comment-form { margin-top: 10px; display: flex; gap: 8px; }
        .comment-form input { flex: 1; padding: 8px; border-radius: 8px; border: 1px solid #ccc; }
        .comment-form button { background: #007bff; color: white; border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; }
        .comment-form button:hover { background: #0056b3; }
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
                    <div class="post-header-left">
                        <img src="/social_network/uploads/<?= htmlspecialchars($post["profile_pic"]) ?>" alt="Profile Picture">
                        <strong><?= htmlspecialchars($post["full_name"]) ?></strong>
                    </div>
                    <!-- ✅ Follow Button -->
                    <?php if ($post["user_id"] != $user_id): ?>
                        <form method="POST">
                            <input type="hidden" name="follow_user_id" value="<?= $post["user_id"] ?>">
                            <button type="submit" name="follow" class="follow-btn">+ Follow</button>
                        </form>
                    <?php endif; ?>
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

                <!-- ✅ Comments Section -->
                <div class="comments">
                    <?php
                    $cstmt = $db->prepare("SELECT comments.*, users.full_name 
                                           FROM comments 
                                           JOIN users ON comments.user_id = users.id 
                                           WHERE comments.post_id=? ORDER BY comments.created_at ASC");
                    $cstmt->execute([$post["id"]]);
                    $comments = $cstmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($comments as $c): ?>
                        <div class="comment">
                            <strong><?= htmlspecialchars($c["full_name"]) ?>:</strong>
                            <?= htmlspecialchars($c["comment"]) ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Add Comment Form -->
                    <form method="POST" class="comment-form">
                        <input type="hidden" name="comment_post_id" value="<?= $post["id"] ?>">
                        <input type="text" name="comment_text" placeholder="Write a comment..." required>
                        <button type="submit" name="add_comment">Post</button>
                    </form>
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
