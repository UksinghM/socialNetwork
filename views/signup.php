<?php
require_once "../config/db.php";
require_once "../classes/User.php";

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$message = "";
$message_class = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"] ?? "";
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    $age = $_POST["age"] ?? 0;
    $profile_pic = $_FILES["profile_pic"] ?? null;

    $result = $user->register($name, $email, $password, $age, $profile_pic);

    if (is_array($result) && $result["status"] === "success") {
        $message = $result["message"];
        $message_class = "success";
    } else {
        $message = is_array($result) ? $result["message"] : "❌ Something broke, fix it!";
        $message_class = "error";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Signup, Loser!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .container {
            width: 100%;
            max-width: 500px;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }
        button:hover {
            background: #218838;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .login-link { text-align: center; margin-top: 20px; }
        .login-link a { color: #007bff; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Create Account, Don’t Screw It Up!</h2>
            <?php if($message): ?>
                <p class="message <?= htmlspecialchars($message_class) ?>"><?= $message ?></p>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="name" placeholder="Full Name, Make It Good" required>
                <input type="email" name="email" placeholder="Email, Don’t Mess It Up" required>
                <input type="password" name="password" placeholder="Password, Keep It Secret" required>
                <input type="number" name="age" placeholder="Age, Be Honest" required>
                <label for="profile_pic">Profile Pic (JPG/PNG, Don’t Be Stupid):</label>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/jpeg,image/png,image/jpg">
                <button type="submit">Register, You Fool!</button>
            </form>
            <div class="login-link">
                <p>Got an account? <a href="login.php">Login, Lazy!</a></p>
            </div>
        </div>
    </div>
</body>
</html>