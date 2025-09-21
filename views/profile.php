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
        move_uploaded_file($_FILES["profile_pic"]["tmp_name"], __DIR__ . "/../uploads/" . $fileName);
        $profile_pic = $fileName;
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
        move_uploaded_file($_FILES["post_img"]["tmp_name"], __DIR__ . "/../uploads/" . $post_img);
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root { --primary-color: #1877F2; --background-color: #f0f2f5; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--background-color);
            margin: 0;
            padding: 0;
        }
        .navbar {
            background: #fff;
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
            color: var(--primary-color);
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }
        .navbar .logo { font-size: 24px; }

        .profile-header {
            background: #fff;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        .profile-header img {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        .profile-header h2 {
            margin: 0;
            font-size: 28px;
        }

        .container {
            display: flex;
            gap: 20px;
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 15px;
        }
        .left-column { flex: 1; }
        .right-column { flex: 2; }

        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        .card h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .about-info p { margin: 5px 0 15px; color: #333; }
        .about-info strong { color: #65676b; }

        input[type="text"], input[type="number"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }
        textarea { resize: vertical; }
        button {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button:hover { background: #166fe5; }

        .post-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        .post-content p { font-size: 15px; line-height: 1.5; margin: 0 0 15px 0; }
        .post-content img { max-width: 100%; border-radius: 10px; margin-top: 10px; }
        .post-footer { color: #65676b; font-size: 13px; margin-bottom: 10px; }
        .post-actions { display: flex; align-items: center; gap: 15px; border-top: 1px solid #eee; padding-top: 10px; }
        .post-actions button, .post-actions form button {
            background: none; border: none; cursor: pointer; font-size: 14px;
            font-weight: bold; color: #65676b; padding: 0;
        }
        .post-actions button:hover, .post-actions form button:hover { text-decoration: underline; }
        .post-actions span { font-size: 14px; color: #65676b; }
        .post-actions form { display: inline; margin-left: auto; }
        .post-actions .delete-btn { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="feed.php" class="logo">SocialNet</a>
        <div>
            <a href="feed.php">Feed</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="profile-header">
        <img src="/social_network/uploads/<?= htmlspecialchars($user["profile_pic"]) ?>" alt="Profile Picture">
        <h2><?= htmlspecialchars($user["full_name"]) ?></h2>
    </div>

    <div class="container">
        <div class="left-column">
            <div class="card">
                <h3>About</h3>
                <div class="about-info">
                    <p><strong>Email:</strong> <?= htmlspecialchars($user["email"]) ?></p>
                    <p><strong>Age:</strong> <?= htmlspecialchars($user["age"]) ?></p>
                </div>
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
        </div>

        <div class="right-column">
            <div class="card">
                <h3>Create Post</h3>
                <form method="POST" enctype="multipart/form-data">
                    <textarea name="description" placeholder="What's on your mind?" required></textarea>
                    <input type="file" name="post_img">
                    <button type="submit" name="add_post">Post</button>
                </form>
            </div>

            <h3>Your Posts</h3>
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
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
                        <span id="likes-<?= $post["id"] ?>"><?= $post['likes'] ?></span>
                        <button class="dislike-btn" data-post="<?= $post["id"] ?>">👎 Dislike</button>
                        <span id="dislikes-<?= $post["id"] ?>"><?= $post['dislikes'] ?></span>
                        
                        <form method="POST">
                            <input type="hidden" name="delete_post_id" value="<?= $post["id"] ?>">
                            <button type="submit" name="delete_post" class="delete-btn">Delete</button>
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
