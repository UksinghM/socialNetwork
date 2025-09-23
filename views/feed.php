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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Feed</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-cover bg-center flex flex-col" style="background-image: url('https://seeromega.com/wp-content/uploads/2016/09/social-networking-websites.jpg');">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

    <!-- Navbar -->
    <nav class="relative z-10 bg-white shadow-md p-4 flex justify-between items-center sticky top-0">
        <a href="feed.php" class="text-2xl font-bold text-blue-600">SocialNet</a>
        <div class="space-x-4">
            <a href="profile.php" class="text-blue-600 font-semibold hover:text-blue-800">My Profile</a>
            <a href="logout.php" class="text-blue-600 font-semibold hover:text-blue-800">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="relative z-10 max-w-3xl mx-auto mt-6 px-4">
        <?php foreach ($posts as $post): ?>
            <div class="bg-white/90 p-6 rounded-xl shadow-md mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <img src="/social_network/uploads/<?= htmlspecialchars($post['profile_pic']) ?>" alt="Profile Picture" class="w-12 h-12 rounded-full mr-4">
                        <strong class="text-lg text-gray-800"><?= htmlspecialchars($post['full_name']) ?></strong>
                    </div>
                    <?php if ($post['user_id'] != $user_id): ?>
                        <form method="POST">
                            <input type="hidden" name="follow_user_id" value="<?= $post['user_id'] ?>">
                            <button type="submit" name="follow" class="bg-blue-600 text-white px-4 py-1 rounded-lg hover:bg-blue-700 transition-colors">+ Follow</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="text-gray-700 mb-4">
                    <p class="text-base leading-relaxed"><?= htmlspecialchars($post['description']) ?></p>
                    <?php if ($post['image']): ?>
                        <img src="/social_network/uploads/<?= htmlspecialchars($post['image']) ?>" alt="Post Image" class="max-w-full rounded-lg mt-4">
                    <?php endif; ?>
                </div>
                <div class="text-gray-500 text-sm mb-4">
                    Posted on <?= date('F j, Y, g:i a', strtotime($post['created_at'])) ?>
                </div>
                <div class="flex items-center gap-4 pt-2 border-t">
                    <button class="like-btn text-blue-600 hover:text-blue-800" data-post="<?= $post['id'] ?>">👍 Like</button>
                    <span id="likes-<?= $post['id'] ?>" class="text-gray-600">0</span>
                    <button class="dislike-btn text-blue-600 hover:text-blue-800" data-post="<?= $post['id'] ?>">👎 Dislike</button>
                    <span id="dislikes-<?= $post['id'] ?>" class="text-gray-600">0</span>
                </div>

                <!-- Comments Section -->
                <div class="mt-4 pt-2 border-t">
                    <?php
                    $cstmt = $db->prepare("SELECT comments.*, users.full_name 
                                           FROM comments 
                                           JOIN users ON comments.user_id = users.id 
                                           WHERE comments.post_id=? ORDER BY comments.created_at ASC");
                    $cstmt->execute([$post['id']]);
                    $comments = $cstmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($comments as $c): ?>
                        <div class="mb-2 text-gray-700">
                            <strong class="text-gray-800"><?= htmlspecialchars($c['full_name']) ?>:</strong>
                            <?= htmlspecialchars($c['comment']) ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Add Comment Form -->
                    <form method="POST" class="flex gap-2 mt-3">
                        <input type="hidden" name="comment_post_id" value="<?= $post['id'] ?>">
                        <input type="text" name="comment_text" placeholder="Write a comment..." required class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" name="add_comment" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">Post</button>
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
                $("#likes-" + post_id).text(res.likes);
                $("#dislikes-" + post_id).text(res.dislikes);
            });
        });
    });
    </script>
</body>
</html>