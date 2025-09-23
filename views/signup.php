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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup, Loser!</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-cover bg-center" style="background-image: url('https://img.aelieve.com/wYiwMmE-No0-ThSP/w:auto/h:auto/q:74/https://cdn.aelieve.com/4441de38-social-media.jpg');">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
    <div class="relative z-10 bg-white/90 p-12 rounded-2xl shadow-2xl max-w-xl w-full flex flex-col items-center">
        <h2 class="text-5xl font-extrabold text-center text-gray-800 mb-10">Create Account, Don’t Screw It Up!</h2>
        <?php if($message): ?>
            <p class="text-center mb-8 text-xl font-semibold <?php echo $message_class === 'success' ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'; ?> px-6 py-3 rounded-lg"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-8 w-full max-w-lg">
            <div>
                <label for="name" class="block text-xl font-semibold text-gray-700 text-center">Full Name, Make It Good</label>
                <input type="text" name="name" id="name" required class="w-full px-6 py-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg" placeholder="Full Name, Make It Good">
            </div>
            <div>
                <label for="email" class="block text-xl font-semibold text-gray-700 text-center">Email, Don’t Mess It Up</label>
                <input type="email" name="email" id="email" required class="w-full px-6 py-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg" placeholder="Email, Don’t Mess It Up">
            </div>
            <div>
                <label for="password" class="block text-xl font-semibold text-gray-700 text-center">Password, Keep It Secret</label>
                <input type="password" name="password" id="password" required class="w-full px-6 py-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg" placeholder="Password, Keep It Secret">
            </div>
            <div>
                <label for="age" class="block text-xl font-semibold text-gray-700 text-center">Age, Be Honest</label>
                <input type="number" name="age" id="age" required class="w-full px-6 py-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg" placeholder="Age, Be Honest">
            </div>
            <div>
                <label for="profile_pic" class="block text-xl font-semibold text-gray-700 text-center">Profile Pic (JPG/PNG, Don’t Be Stupid)</label>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/jpeg,image/png,image/jpg" class="w-full px-6 py-4 text-gray-700 text-lg">
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-4 rounded-lg hover:bg-green-700 transition-colors text-xl font-bold">Register, You Fool!</button>
        </form>
        <div class="text-center mt-8">
            <p class="text-lg text-gray-700">Got an account? <a href="login.php" class="text-blue-600 hover:underline font-semibold">Login, Lazy!</a></p>
        </div>
    </div>
</body>
</html>