

<?php
session_start();

$host = "127.0.0.1";      // ใช้ IP ตรง ๆ แทน localhost
$dbname = "gift_finder";  // ชื่อ DB ต้องมีใน phpMyAdmin
$username = "root";       // XAMPP ปกติ
$password = "";           // XAMPP ปกติไม่ตั้งรหัส

try {
    // ถ้า XAMPP ใช้ port 3306 (ปกติ) ใช้แบบนี้ได้เลย
    $pdo = new PDO(
        "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
