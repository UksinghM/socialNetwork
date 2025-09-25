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

// ✅ Fetch user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Fetch posts with likes/dislikes counts
$stmt = $db->prepare("
    SELECT 
        posts.*,
        COALESCE(SUM(CASE WHEN likes.type = 'like' THEN 1 ELSE 0 END), 0) as likes,
        COALESCE(SUM(CASE WHEN likes.type = 'dislike' THEN 1 ELSE 0 END), 0) as dislikes
    FROM posts 
    LEFT JOIN likes ON likes.post_id = posts.id
    WHERE posts.user_id=? 
    GROUP BY posts.id
    ORDER BY posts.created_at DESC
");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Fetch pending friend requests
$stmt = $db->prepare("
    SELECT friends.id, users.full_name, users.profile_pic 
    FROM friends 
    JOIN users ON friends.user_id = users.id 
    WHERE friends.friend_id=? AND friends.status='pending'
");
$stmt->execute([$user_id]);
$friend_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Followers & Following count
$stmt = $db->prepare("SELECT COUNT(*) FROM followers WHERE user_id=?");
$stmt->execute([$user_id]);
$followers_count = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM followers WHERE follower_id=?");
$stmt->execute([$user_id]);
$following_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-cover bg-center flex flex-col" style="background-image:url('https://seeromega.com/wp-content/uploads/2016/09/social-networking-websites.jpg');">
  <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

  <!-- Navbar -->
  <nav class="relative z-10 bg-white shadow-md p-4 flex justify-between items-center sticky top-0">
    <a href="feed.php" class="text-2xl font-bold text-blue-600">SocialNet</a>
    <div class="space-x-4">
      <a href="feed.php" class="text-blue-600 font-semibold hover:text-blue-800">Feed</a>
      <a href="logout.php" class="text-blue-600 font-semibold hover:text-blue-800">Logout</a>
    </div>
  </nav>

  <!-- Profile Header -->
  <div class="relative z-10 max-w-4xl mx-auto mt-8 p-6 bg-white/90 rounded-xl shadow-xl text-center">
    <img src="/social_network/uploads/<?= htmlspecialchars($user['profile_pic']) ?>" class="w-32 h-32 rounded-full border-4 border-white shadow-md mx-auto">
    <h2 id="displayName" class="text-3xl font-bold text-gray-800 mt-4 inline-block relative group">
      <?= htmlspecialchars($user['full_name']) ?>
      <button id="editNameBtn" class="hidden group-hover:inline ml-2 text-blue-500">✏️</button>
    </h2>
    <input type="text" id="editNameInput" class="hidden border px-2 py-1 rounded" value="<?= htmlspecialchars($user['full_name']) ?>">
    <p class="text-gray-600 mt-2">👥 Followers: <?= $followers_count ?> | Following: <?= $following_count ?></p>
  </div>

  <!-- Main Content -->
  <div class="relative z-10 max-w-4xl mx-auto mt-6 flex flex-col md:flex-row gap-6 px-4">
    <!-- Left Column -->
    <div class="w-full md:w-1/3 space-y-6">
      <!-- About -->
      <div class="bg-white/90 p-6 rounded-xl shadow-md">
        <h3 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">About</h3>
        <div class="text-gray-700">
          <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
          <p><strong>Age:</strong> 
            <span id="displayAge"><?= htmlspecialchars($user['age']) ?></span>
            <button id="editAgeBtn" class="ml-2 text-blue-500">✏️</button>
          </p>
          <input type="number" id="editAgeInput" class="hidden border px-2 py-1 rounded" value="<?= htmlspecialchars($user['age']) ?>">
        </div>
      </div>

      <!-- Friend Requests -->
      <div class="bg-white/90 p-6 rounded-xl shadow-md">
        <h3 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Friend Requests</h3>
        <?php if ($friend_requests): ?>
          <?php foreach ($friend_requests as $req): ?>
            <div class="flex items-center gap-3 mb-3">
              <img src="/social_network/uploads/<?= htmlspecialchars($req['profile_pic']) ?>" class="w-10 h-10 rounded-full">
              <span class="flex-1 text-gray-700"><?= htmlspecialchars($req['full_name']) ?></span>
              <form method="POST" class="flex gap-2">
                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                <button type="submit" name="accept_friend" class="bg-green-500 text-white px-3 py-1 rounded-lg hover:bg-green-600">Accept</button>
                <button type="submit" name="reject_friend" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600">Reject</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-gray-600">No new requests</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right Column -->
    <div class="w-full md:w-2/3 space-y-6">
      <!-- Create Post -->
      <div class="bg-white/90 p-6 rounded-xl shadow-md">
        <h3 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Create Post</h3>
        <form id="addPostForm" enctype="multipart/form-data" class="space-y-4">
          <textarea name="description" placeholder="What's on your mind?" required class="w-full px-4 py-2 border rounded-lg"></textarea>
          <input type="file" name="post_img" class="w-full px-4 py-2">
          <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Post</button>
        </form>
      </div>

      <!-- User Posts -->
      <h3 class="text-xl font-semibold text-gray-800">Your Posts</h3>
      <?php foreach ($posts as $post): ?>
        <div id="post-<?= $post['id'] ?>" class="bg-white/90 p-6 rounded-xl shadow-md">
          <div class="text-gray-700">
            <p><?= htmlspecialchars($post['description']) ?></p>
            <?php if ($post['image']): ?>
              <img src="/social_network/uploads/<?= htmlspecialchars($post['image']) ?>" class="max-w-full rounded-lg mt-4">
            <?php endif; ?>
          </div>
          <div class="text-gray-500 text-sm mt-2">
            Posted on <?= date('F j, Y, g:i a', strtotime($post['created_at'])) ?>
          </div>
          <div class="flex items-center gap-4 mt-4 pt-2 border-t">
            <button class="like-btn text-blue-600 hover:text-blue-800" data-post="<?= $post['id'] ?>">👍 Like</button>
            <span id="likes-<?= $post['id'] ?>"><?= $post['likes'] ?></span>
            <button class="dislike-btn text-blue-600 hover:text-blue-800" data-post="<?= $post['id'] ?>">👎 Dislike</button>
            <span id="dislikes-<?= $post['id'] ?>"><?= $post['dislikes'] ?></span>
            <button class="delete-post text-red-500 hover:text-red-700 ml-auto" data-post="<?= $post['id'] ?>">Delete</button>
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
            <form class="comment-form flex gap-2 mt-3">
              <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
              <input type="text" name="text" placeholder="Write a comment..." required class="flex-1 px-4 py-2 border rounded-lg">
              <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Post</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

<script>
$(function(){
  // ✅ Like/Dislike
  $(".like-btn, .dislike-btn").click(function(){
    let post_id = $(this).data("post");
    let type = $(this).hasClass("like-btn") ? "like" : "dislike";
    $.post("../api/like_post.php", { post_id, type }, function(data){
      let res = (typeof data === 'string') ? JSON.parse(data) : data;
      $("#likes-"+post_id).text(res.likes);
      $("#dislikes-"+post_id).text(res.dislikes);
    });
  });

  // ✅ Delete Post AJAX
  $(".delete-post").click(function(){
    if(!confirm("Delete this post?")) return;
    let post_id = $(this).data("post");
    $.post("../api/delete_post.php", { post_id }, function(res){
      if(res.status === "success") {
        $("#post-"+post_id).remove();
      }
    }, "json");
  });

  // ✅ Add Comment AJAX
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

  // ✅ Inline Edit Name
  $("#editNameBtn").click(function(){
    $("#displayName").hide();
    $("#editNameInput").show().focus();
  });

  $("#editNameInput").blur(function(){
    let newName = $(this).val();
    $.post("../api/update_profile.php", { field: "name", value: newName }, function(res){
      if(res.status === "success"){
        $("#displayName").text(newName).show();
        $("#editNameInput").hide();
      }
    }, "json");
  });

  // ✅ Inline Edit Age
  $("#editAgeBtn").click(function(){
    $("#displayAge").hide();
    $("#editAgeInput").show().focus();
  });

  $("#editAgeInput").blur(function(){
    let newAge = $(this).val();
    $.post("../api/update_profile.php", { field: "age", value: newAge }, function(res){
      if(res.status === "success"){
        $("#displayAge").text(newAge).show();
        $("#editAgeInput").hide();
      }
    }, "json");
  });

  // ✅ Create Post AJAX
  $("#addPostForm").submit(function(e){
    e.preventDefault();
    let formData = new FormData(this);
    $.ajax({
      url: "../api/add_post.php",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      success: function(res){
        if(res.status === "success"){ location.reload(); }
      }
    });
  });
});
</script>
</body>
</html>
