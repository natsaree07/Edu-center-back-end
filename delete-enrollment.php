<?php
// delete-enrollment.php

// ตั้งค่า header ให้ส่ง response เป็น JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // อนุญาตให้ Angular เข้าถึง (ปรับตาม domain จริง)
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// การเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root"; // เปลี่ยนตาม username ของคุณ
$password = ""; // เปลี่ยนตาม password ของคุณ
$dbname = "tutordb"; // เปลี่ยนตามชื่อฐานข้อมูลของคุณ

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "error" => "Connection failed: " . $conn->connect_error
    ]));
}

// รับข้อมูลจาก request (POST)
$input = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบว่ามีข้อมูลครบหรือไม่
if (!isset($input['stdCode']) || !isset($input['opcCode'])) {
    echo json_encode([
        "success" => false,
        "error" => "Missing stdCode or opcCode"
    ]);
    exit();
}

$stdCode = $input['stdCode'];
$opcCode = $input['opcCode'];

// เตรียมคำสั่ง SQL ด้วย prepared statement เพื่อป้องกัน SQL Injection
$sql = "DELETE FROM enrollment WHERE stdCode = ? AND opcCode = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode([
        "success" => false,
        "error" => "Prepare failed: " . $conn->error
    ]);
    exit();
}

// Bind parameters (i = integer)
$stmt->bind_param("ii", $stdCode, $opcCode);

// รันคำสั่ง
if ($stmt->execute()) {
    // ตรวจสอบว่ามีแถวถูกลบหรือไม่
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "success" => true,
            "message" => "ลบการลงทะเบียนสำเร็จ"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "ไม่พบข้อมูลการลงทะเบียนที่ต้องการลบ"
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "error" => "Execute failed: " . $stmt->error
    ]);
}

// ปิด statement และ connection
$stmt->close();
$conn->close();
?>