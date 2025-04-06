<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// รับข้อมูล JSON ที่ส่งมาจาก Angular
$data = json_decode(file_get_contents("php://input"), true);

$conn = new mysqli("localhost", "root", "", "TutorDB");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "เชื่อมต่อฐานข้อมูลล้มเหลว"]);
    exit;
}

// ตรวจสอบว่าได้ข้อมูลที่จำเป็นไหม
if (
    empty($data['stdCode']) || empty($data['stdFName']) || empty($data['stdLName']) ||
    empty($data['stdFaculty']) || empty($data['stdMajor']) || empty($data['stdEmail'])
) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE student
    SET stdFName = ?, stdLName = ?, stdFaculty = ?, stdMajor = ?, stdEmail = ?
    WHERE stdCode = ?
");

$stmt->bind_param(
    "sssssi",
    $data['stdFName'],
    $data['stdLName'],
    $data['stdFaculty'],
    $data['stdMajor'],
    $data['stdEmail'],
    $data['stdCode']
);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "อัปเดตข้อมูลสำเร็จ"]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "อัปเดตไม่สำเร็จ: " . $stmt->error]);
}

$stmt->close();
$conn->close();
