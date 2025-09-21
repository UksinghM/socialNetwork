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

// Fetch user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    $name = $_POST["name"];
    $age = $_POST["age"];

    $profile_pic = $user["profile_pic"];
    if (!empty($_FILES["profile_pic"]["name"])) {
        $fileName = time() . "_" . basename($_FILES["profile_pic"]["name"]);
        $uploadPath = __DIR__ . "/../uploads/" . $fileName;

        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $uploadPath)) {
            $profile_pic = $fileName;
        }
    }

    $stmt = $db->prepare("UPDATE users SET full_name=?, age=?, profile_pic=? WHERE id=?");
    $stmt->execute([$name, $age, $profile_pic, $user_id]);

    $_SESSION["name"] = $name;
    header("Location: profile.php");
    exit;
}

// Handle new post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_post"])) {
    $description = $_POST["description"];
    $post_img = null;

    if (!empty($_FILES["post_img"]["name"])) {
        $post_img = time() . "_" . basename($_FILES["post_img"]["name"]);
        $uploadPath = __DIR__ . "/../uploads/" . $post_img;

        if (move_uploaded_file($_FILES["post_img"]["tmp_name"], $uploadPath)) {
            // ✅ only save if upload worked
        } else {
            $post_img = null;
        }
    }

    $stmt = $db->prepare("INSERT INTO posts (user_id, description, image) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $description, $post_img]);
    header("Location: profile.php");
    exit;
}

// Handle delete post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_post"])) {
    $post_id = $_POST["delete_post_id"];
    $stmt = $db->prepare("DELETE FROM posts WHERE id=? AND user_id=?");
    $stmt->execute([$post_id, $user_id]);
    header("Location: profile.php");
    exit;
}

// Fetch posts
$stmt = $db->prepare("SELECT * FROM posts WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: #007bff;
            color: white;
            padding: 15px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
            font-weight: bold;
        }
        .container {
            width: 70%;
            margin: 20px auto;
        }
        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h2, h3 {
            color: #333;
        }
        input, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 6px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: #0056b3;
        }
        .post {
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .post img {
            margin-top: 10px;
            border-radius: 8px;
            max-width: 100%;
        }
        .post small {
            color: gray;
        }
        .actions {
            margin-top: 10px;
        }
        .actions button {
            width: auto;
            padding: 5px 12px;
            margin-right: 10px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <!-- ✅ Navigation -->
    <div class="navbar">
        <a href="feed.php">Go to Feed</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <div class="card">
            <h2>Welcome, <?= htmlspecialchars($user["full_name"]) ?> 👋</h2>
            <p><strong>Email:</strong> <?= htmlspecialchars($user["email"]) ?></p>
            <p><strong>Age:</strong> <?= htmlspecialchars($user["age"]) ?></p>
            <img src="/social_network/uploads/<?= htmlspecialchars($user["profile_pic"]) ?>" width="120" style="border-radius:50%; border:3px solid #007bff;">
        </div>

        <div class="card">
            <h3>Edit Profile</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="name" value="<?= htmlspecialchars($user["full_name"]) ?>" required>
                <input type="number" name="age" value="<?= htmlspecialchars($user["age"]) ?>" required>
                <input type="file" name="profile_pic">
                <button type="submit" name="update_profile">Update Profile</button>
            </form>
        </div>

        <div class="card">
            <h3>Create Post</h3>
            <form method="POST" enctype="multipart/form-data">
                <textarea name="description" placeholder="What's on your mind?" required></textarea>
                <input type="file" name="post_img">
                <button type="submit" name="add_post">Post</button>
            </form>
        </div>

        <div class="card">
            <h3>Your Posts</h3>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <p><?= htmlspecialchars($post["description"]) ?></p>
                    <?php if ($post["image"]): ?>
                        <img src="/social_network/uploads/<?= htmlspecialchars($post["image"]) ?>" width="250"><br>
                    <?php endif; ?>
                    <small>Posted on <?= $post["created_at"] ?></small><br>

                    <div class="actions">
                        <!-- Delete button -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_post_id" value="<?= $post["id"] ?>">
                            <button type="submit" name="delete_post">🗑 Delete</button>
                        </form>

                        <!-- Like / Dislike buttons -->
                        <button class="like-btn" data-post="<?= $post["id"] ?>">👍 Like</button>
                        <button class="dislike-btn" data-post="<?= $post["id"] ?>">👎 Dislike</button>
                        <span id="likes-<?= $post["id"] ?>"></span>
                        <span id="dislikes-<?= $post["id"] ?>"></span>
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
                $("#likes-" + post_id).text(" Likes: " + res.likes);
                $("#dislikes-" + post_id).text(" Dislikes: " + res.dislikes);
            });
        });
    });
    </script>
</body>
</html>
