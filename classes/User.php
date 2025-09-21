<?php
class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register($name, $email, $password, $age, $profile_pic) {
        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            return "❌ Email already exists!";
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Handle profile picture upload
        $fileName = "default.png"; // default
        if ($profile_pic && $profile_pic['name']) {
            $fileName = time() . "_" . basename($profile_pic["name"]);
            $uploadPath = __DIR__ . "/../uploads/" . $fileName;
            move_uploaded_file($profile_pic["tmp_name"], $uploadPath);
        }

        // Insert into DB
        $stmt = $this->conn->prepare("INSERT INTO users (full_name, email, password, age, profile_pic) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $hashed_password, $age, $fileName])) {
            return true;
        }
        return "❌ Registration failed!";
    }
}
