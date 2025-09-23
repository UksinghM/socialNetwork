<?php
session_start();
require_once "../config/db.php";

$database = new Database();
$db = $database->getConnection();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Check if email exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
        // Login success -> save session
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["name"] = $user["full_name"];

        header("Location: profile.php");
        exit;
    } else {
        $message = "❌ Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-cover bg-center" style="background-image: url('https://img.aelieve.com/wYiwMmE-No0-ThSP/w:auto/h:auto/q:74/https://cdn.aelieve.com/4441de38-social-media.jpg');">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div class="relative z-10 bg-white/90 p-12 rounded-xl shadow-2xl max-w-lg w-full flex flex-col items-center">
        <h2 class="text-4xl font-bold text-center text-gray-800 mb-8">Login</h2>
        <?php if($message): ?>
            <p class="text-red-500 text-center mb-6 text-lg"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST" class="space-y-6 w-full max-w-md">
            <div>
                <label for="email" class="block text-lg font-medium text-gray-700 text-center">Email</label>
                <input type="email" name="email" id="email" required class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg">
            </div>
            <div>
                <label for="password" class="block text-lg font-medium text-gray-700 text-center">Password</label>
                <input type="password" name="password" id="password" required class="w-full px-5 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors text-lg font-semibold">Login</button>
        </form>
    </div>
</body>
</html>