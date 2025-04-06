<?php
// ตั้งค่า Content-Type และ CORS Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// จัดการ preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// เปิดการรายงานข้อผิดพลาด (สำหรับ debug)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// ฟังก์ชันส่ง error response
function sendError($message) {
    http_response_code(400);
    echo json_encode(["error" => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

// ฟังก์ชันส่ง success response
function sendSuccess($message) {
    http_response_code(200);
    echo json_encode(["success" => true, "message" => $message], JSON_UNESCAPED_UNICODE);
    exit();
}

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "tutordb");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    sendError("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Method ไม่ถูกต้อง ต้องใช้ POST");
}

// อ่านข้อมูลจาก request body
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    sendError("ข้อมูล JSON ไม่ถูกต้อง: " . json_last_error_msg());
}

// ตรวจสอบข้อมูลที่ส่งมา
$stdCode = isset($input['stdCode']) ? (int)$input['stdCode'] : null;
$opcCode = isset($input['opcCode']) ? (int)$input['opcCode'] : null;

// Debug: บันทึกข้อมูลที่ได้รับ
error_log("Received data: " . json_encode($input, JSON_UNESCAPED_UNICODE));
error_log("stdCode: $stdCode, opcCode: $opcCode");

// ตรวจสอบข้อมูล
if ($stdCode === null || $stdCode <= 0) {
    sendError("รหัสนักเรียน (stdCode) ไม่ถูกต้อง: $stdCode");
}
if ($opcCode === null || $opcCode <= 0) {
    sendError("รหัสคอร์ส (opcCode) ไม่ถูกต้อง: $opcCode");
}

// ตรวจสอบว่านักเรียนและคอร์สมีอยู่ในระบบหรือไม่
// ตรวจสอบนักเรียน
$stmt = $conn->prepare("SELECT stdCode FROM student WHERE stdCode = ?");
$stmt->bind_param("i", $stdCode);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendError("ไม่พบนักเรียนที่มีรหัส $stdCode ในระบบ");
}
$stmt->close();

// ตรวจสอบคอร์ส
$stmt = $conn->prepare("SELECT opcCode FROM opencourse WHERE opcCode = ?");
$stmt->bind_param("i", $opcCode);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    sendError("ไม่พบคอร์สที่มีรหัส $opcCode ในระบบ");
}
$stmt->close();

// ตรวจสอบว่านักเรียนลงทะเบียนคอร์สนี้แล้วหรือยัง
$stmt = $conn->prepare("SELECT * FROM enrollment WHERE stdCode = ? AND opcCode = ?");
$stmt->bind_param("ii", $stdCode, $opcCode);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    sendError("นักเรียนรหัส $stdCode ได้ลงทะเบียนคอร์ส $opcCode นี้แล้ว");
}
$stmt->close();

// บันทึกข้อมูลการลงทะเบียน
$stmt = $conn->prepare("INSERT INTO enrollment (stdCode, opcCode) VALUES (?, ?)");
$stmt->bind_param("ii", $stdCode, $opcCode);

if ($stmt->execute()) {
    sendSuccess("ลงทะเบียนสำเร็จสำหรับนักเรียนรหัส $stdCode ในคอร์ส $opcCode");
} else {
    sendError("เกิดข้อผิดพลาดในการลงทะเบียน: " . $stmt->error);
}

// ปิด statement และการเชื่อมต่อ
$stmt->close();
$conn->close();
?>