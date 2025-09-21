<?php
class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 🔹 Register new user
    public function register($name, $email, $password, $age, $profile_pic) {
        // Trim + sanitize
        $name = trim($name);
        $email = trim(strtolower($email));
        $age = (int)$age;

        // Check if email already exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            return ["status" => "error", "message" => "❌ Email already exists, pick another!"];
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Handle profile picture
        $fileName = "default.png";
        if ($profile_pic && $profile_pic['name']) {
            $allowedTypes = ["image/jpeg", "image/png", "image/jpg"];
            if (in_array($profile_pic["type"], $allowedTypes)) {
                $fileName = time() . "_" . basename($profile_pic["name"]);
                $uploadPath = __DIR__ . "/../uploads/" . $fileName;
                if (!move_uploaded_file($profile_pic["tmp_name"], $uploadPath)) {
                    return ["status" => "error", "message" => "❌ Profile pic upload failed, try again!"];
                }
            } else {
                return ["status" => "error", "message" => "❌ Only JPG/PNG allowed, you idiot!"];
            }
        }

        // Insert into DB
        $stmt = $this->conn->prepare("INSERT INTO users (full_name, email, password, age, profile_pic) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $hashed_password, $age, $fileName])) {
            return ["status" => "success", "message" => "✅ Registration successful, you genius! Now <a href='login.php'>Login</a>.", "user_id" => $this->conn->lastInsertId()];
        }
        return ["status" => "error", "message" => "❌ Registration failed, something’s broken!"];
    }

    // 🔹 Login user
    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            return $user; // ✅ return user details
        }
        return false;
    }

    // 🔹 Get user by ID
    public function getUserById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 🔹 Search users (for friend system)
    public function searchUsers($keyword) {
        $keyword = "%" . $keyword . "%";
        $stmt = $this->conn->prepare("SELECT id, full_name, email, profile_pic FROM users WHERE full_name LIKE ? OR email LIKE ?");
        $stmt->execute([$keyword, $keyword]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Get all friends of a user
    public function getFriends($user_id) {
        $stmt = $this->conn->prepare("
            SELECT u.id, u.full_name, u.profile_pic 
            FROM friends f
            JOIN users u ON (u.id = f.user_id OR u.id = f.friend_id)
            WHERE (f.user_id=? OR f.friend_id=?) AND f.status='accepted' AND u.id!=?
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>