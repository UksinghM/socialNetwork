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

// ✅ Handle follow (still form-based, can later move to AJAX if needed)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["follow"])) {
    $follow_user_id = $_POST["follow_user_id"];
    if ($follow_user_id != $user_id) {
        $stmt = $db->prepare("INSERT IGNORE INTO followers (user_id, follower_id) VALUES (?, ?)");
        $stmt->execute([$follow_user_id, $user_id]);
    }
    header("Location: feed.php");
    exit;
}

// ✅ Fetch posts with user info
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
<body class="min-h-screen bg-cover bg-center flex flex-col" style="background-image:url('https://seeromega.com/wp-content/uploads/2016/09/social-networking-websites.jpg');">
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
      <div id="post-<?= $post['id'] ?>" class="bg-white/90 p-6 rounded-xl shadow-md mb-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center">
            <img src="/social_network/uploads/<?= htmlspecialchars($post['profile_pic']) ?>" class="w-12 h-12 rounded-full mr-4">
            <strong class="text-lg text-gray-800"><?= htmlspecialchars($post['full_name']) ?></strong>
          </div>
          <?php if ($post['user_id'] != $user_id): ?>
            <form method="POST">
              <input type="hidden" name="follow_user_id" value="<?= $post['user_id'] ?>">
              <button type="submit" name="follow" class="bg-blue-600 text-white px-4 py-1 rounded-lg hover:bg-blue-700">+ Follow</button>
            </form>
          <?php endif; ?>
        </div>

        <div class="text-gray-700 mb-4">
          <p><?= htmlspecialchars($post['description']) ?></p>
          <?php if ($post['image']): ?>
            <img src="/social_network/uploads/<?= htmlspecialchars($post['image']) ?>" class="max-w-full rounded-lg mt-4">
          <?php endif; ?>
        </div>
        <div class="text-gray-500 text-sm mb-4">
          Posted on <?= date('F j, Y, g:i a', strtotime($post['created_at'])) ?>
        </div>

        <!-- Like/Dislike -->
        <div class="flex items-center gap-4 pt-2 border-t">
          <button class="like-btn text-blue-600 hover:text-blue-800" data-post="<?= $post['id'] ?>">👍 Like</button>
          <span id="likes-<?= $post['id'] ?>">0</span>
          <button class="dislike-btn text-blue-600 hover:text-blue-800" data-post="<?= $post['id'] ?>">👎 Dislike</button>
          <span id="dislikes-<?= $post['id'] ?>">0</span>
        </div>

        <!-- Comments -->
        <div class="comments mt-4 pt-2 border-t">
          <div class="comments-list">
            <?php
            $cstmt = $db->prepare("SELECT comments.*, users.full_name FROM comments JOIN users ON comments.user_id=users.id WHERE comments.post_id=? ORDER BY comments.created_at ASC");
            $cstmt->execute([$post['id']]);
            $comments = $cstmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($comments as $c): ?>
              <div class="mb-2 text-gray-700">
                <strong class="text-gray-800"><?= htmlspecialchars($c['full_name']) ?>:</strong>
                <?= htmlspecialchars($c['comment']) ?>
              </div>
            <?php endforeach; ?>
          </div>
          <!-- AJAX comment form -->
          <form class="comment-form flex gap-2 mt-3">
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
            <input type="text" name="text" placeholder="Write a comment..." required class="flex-1 px-4 py-2 border rounded-lg">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Post</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<script>
$(function(){
  // Like/Dislike
  $(".like-btn, .dislike-btn").click(function(){
    let post_id = $(this).data("post");
    let type = $(this).hasClass("like-btn") ? "like" : "dislike";
    $.post("../api/like_post.php", { post_id, type }, function(data){
      let res = (typeof data === 'string') ? JSON.parse(data) : data;
      $("#likes-"+post_id).text(res.likes);
      $("#dislikes-"+post_id).text(res.dislikes);
    });
  });

  // Add Comment AJAX
  $(".comment-form").submit(function(e){
    e.preventDefault();
    let form = $(this);
    let post_id = form.find("input[name=post_id]").val();
    let text = form.find("input[name=text]").val();
    $.post("../api/add_comment.php", { post_id, text }, function(res){
      if(res.status === "success"){
        form.prev(".comments-list").append(
          `<div class="mb-2 text-gray-700"><strong class="text-gray-800">${res.comment.full_name}:</strong> ${res.comment.comment}</div>`
        );
        form[0].reset();
      }
    }, "json");
  });
});
</script>
</body>
</html>
