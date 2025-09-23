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

// ✅ Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $name = $_POST["name"];
    $age = $_POST["age"];

    $profile_pic = $user["profile_pic"];
    if (!empty($_FILES["profile_pic"]["name"])) {
        $fileName = time() . "_" . basename($_FILES["profile_pic"]["name"]);
        move_uploaded_file($_FILES["profile_pic"]["tmp_name"], __DIR__ . "/../Uploads/" . $fileName);
        $profile_pic = $fileName;
    }

    $stmt = $db->prepare("UPDATE users SET full_name=?, age=?, profile_pic=? WHERE id=?");
    $stmt->execute([$name, $age, $profile_pic, $user_id]);

    $_SESSION["name"] = $name;
    header("Location: profile.php");
    exit;
}

// ✅ Handle new post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_post"])) {
    $description = $_POST["description"];
    $post_img = null;

    if (!empty($_FILES["post_img"]["name"])) {
        $post_img = time() . "_" . basename($_FILES["post_img"]["name"]);
        move_uploaded_file($_FILES["post_img"]["tmp_name"], __DIR__ . "/../Uploads/" . $post_img);
    }

    $stmt = $db->prepare("INSERT INTO posts (user_id, description, image) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $description, $post_img]);
    header("Location: profile.php");
    exit;
}

// ✅ Handle delete post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_post"])) {
    $post_id = $_POST["delete_post_id"];
    $stmt = $db->prepare("DELETE FROM posts WHERE id=? AND user_id=?");
    $stmt->execute([$post_id, $user_id]);
    header("Location: profile.php");
    exit;
}

// ✅ Handle friend requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["accept_friend"])) {
        $stmt = $db->prepare("UPDATE friends SET status='accepted' WHERE id=?");
        $stmt->execute([$_POST["request_id"]]);
    }
    if (isset($_POST["reject_friend"])) {
        $stmt = $db->prepare("DELETE FROM friends WHERE id=?");
        $stmt->execute([$_POST["request_id"]]);
    }
    header("Location: profile.php");
    exit;
}

// ✅ Fetch posts with likes/dislikes count
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
<body class="min-h-screen bg-cover bg-center flex flex-col" style="background-image: url('https://seeromega.com/wp-content/uploads/2016/09/social-networking-websites.jpg');">
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
        <img src="/social_network/uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile Picture" class="w-32 h-32 rounded-full border-4 border-white shadow-md mx-auto">
        <h2 class="text-3xl font-bold text-gray-800 mt-4"><?= htmlspecialchars($user['full_name']) ?></h2>
        <p class="text-gray-600 mt-2">👥 Followers: <?= $followers_count ?> | Following: <?= $following_count ?></p>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 max-w-4xl mx-auto mt-6 flex gap-6 px-4">
        <!-- Left Column -->
        <div class="w-1/3 space-y-6">
            <div class="bg-white/90 p-6 rounded-xl shadow-md">
                <h3 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">About</h3>
                <div class="text-gray-700">
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Age:</strong> <?= htmlspecialchars($user['age']) ?></p>
                </div>
            </div>

            <div class="bg-white/90 p-6 rounded-xl shadow-md">
                <h3 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Edit Profile</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="text" name="name" value="<?= htmlspecialchars($user['full_name']) ?>" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="number" name="age" value="<?= htmlspecialchars($user['age']) ?>" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="file" name="profile_pic" class="w-full px-4 py-2">
                    <button type="submit" name="update_profile" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">Update Profile</button>
                </form>
            </div>

            <div class="bg-white/90 p-6 rounded-xl shadow-md">
                <h3 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Friend Requests</h3>
                <?php if ($friend_requests): ?>
                    <?php foreach ($friend_requests as $req): ?>
                        <div class="flex items-center gap-3 mb-3">
                            <img src="/social_network/uploads/<?= htmlspecialchars($req['profile_pic']) ?>" alt="Profile" class="w-10 h-10 rounded-full">
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
        <div class="w-2/3 space-y-6">
            <div class="bg-white/90 p-6 rounded-xl shadow-md">
                <h3 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">Create Post</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <textarea name="description" placeholder="What's on your mind?" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    <input type="file" name="post_img" class="w-full px-4 py-2">
                    <button type="submit" name="add_post" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">Post</button>
                </form>
            </div>

            <h3 class="text-xl font-semibold text-gray-800">Your Posts</h3>
            <?php foreach ($posts as $post): ?>
                <div class="bg-white/90 p-6 rounded-xl shadow-md">
                    <div class="text-gray-700">
                        <p><?= htmlspecialchars($post['description']) ?></p>
                        <?php if ($post['image']): ?>
                            <img src="/social_network/uploads/<?= htmlspecialchars($post['image']) ?>" alt="Post Image" class="max-w-full rounded-lg mt-4">
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
                        <form method="POST" class="ml-auto">
                            <input type="hidden" name="delete_post_id" value="<?= $post['id'] ?>">
                            <button type="submit" name="delete_post" class="text-red-500 hover:text-red-700">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
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