<?php
require_once "../config/db.php";

$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "✅ Database connection successful!";
}
