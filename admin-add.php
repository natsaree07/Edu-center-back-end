<?php
header('Content-Type: application/json');

// เชื่อมต่อฐานข้อมูล
$host = "localhost";
$user = "root";
$pass = "";
$db = "tutordb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'DB connection failed']));
}

mysqli_set_charset($conn, "utf8mb4");

// ตั้งค่าข้อมูลแอดมิน (สามารถปรับให้รับจาก POST ก็ได้)
$username = 'admin@gmail.com';
$plainPassword = '0853292585';
$name = 'นัสรี ม่องพร้า';
$role = 'superadmin';

// เข้ารหัสรหัสผ่าน
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

// เพิ่มเข้า DB
$stmt = $conn->prepare("INSERT INTO admin (username, password, name, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $hashedPassword, $name, $role);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Admin created successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Duplicate or insert failed']);
}

$stmt->close();
$conn->close();
