<?php
require_once "../config/db.php";
require_once "../classes/User.php";

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $age = $_POST["age"];
    $profile_pic = $_FILES["profile_pic"];

    $result = $user->register($name, $email, $password, $age, $profile_pic);

    if ($result === true) {
        $message = "✅ Registration successful! You can now <a href='login.php'>Login</a>.";
    } else {
        $message = $result;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Signup</title>
</head>
<body>
    <h2>Signup Form</h2>
    <?php if($message) echo "<p>$message</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <label>Full Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Age:</label><br>
        <input type="number" name="age" required><br><br>

        <label>Profile Picture:</label><br>
        <input type="file" name="profile_pic"><br><br>

        <button type="submit">Register</button>
    </form>
</body>
</html>
