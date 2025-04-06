<?php
// เปิด error reporting เพื่อ debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ตั้งค่า CORS Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// จัดการ Preflight Request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// รับข้อมูลจาก body
$data = json_decode(file_get_contents("php://input"), true);
error_log("Received data: " . print_r($data, true));

// ตรวจสอบ JSON format
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON format: " . json_last_error_msg()]);
    exit;
}

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($data['tutFName'], $data['tutLName'], $data['tupNumber'], $data['tutCode'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields", "received" => $data]);
    exit;
}

// ตรวจสอบรูปแบบหมายเลขโทรศัพท์
$tupNumber = preg_replace("/[^0-9]/", "", $data['tupNumber']);
if (!preg_match("/^[0-9]{10}$/", $tupNumber)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid phone number format (must be 10 digits)"]);
    exit;
}
$data['tupNumber'] = $tupNumber;

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "TutorDB");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// เริ่ม transaction
$conn->begin_transaction();

try {
    // ตรวจสอบว่า tutCode มีอยู่ในตาราง tutor หรือไม่
    $checkStmt = $conn->prepare("SELECT tupNo FROM tutor WHERE tutCode = ?");
    if ($checkStmt === false) {
        throw new Exception("Failed to prepare check statement: " . $conn->error);
    }
    $checkStmt->bind_param("i", $data['tutCode']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("No tutor found with tutCode: " . $data['tutCode']);
    }
    $row = $result->fetch_assoc();
    $tupNo = $row['tupNo'];
    $checkStmt->close();

    // อัปเดตตาราง tutor
    $stmt = $conn->prepare("UPDATE tutor SET tutFName=?, tutLName=? WHERE tutCode=?");
    if ($stmt === false) {
        throw new Exception("Failed to prepare tutor update statement: " . $conn->error);
    }
    $stmt->bind_param("ssi", $data['tutFName'], $data['tutLName'], $data['tutCode']);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update tutor: " . $stmt->error);
    }
    $tutorAffectedRows = $stmt->affected_rows;

    // อัปเดตตาราง tutphone
    $stmtPhone = $conn->prepare("UPDATE tutphone SET tupNumber=? WHERE tupNo=?");
    if ($stmtPhone === false) {
        throw new Exception("Failed to prepare phone update statement: " . $conn->error);
    }
    $stmtPhone->bind_param("si", $data['tupNumber'], $tupNo);
    if (!$stmtPhone->execute()) {
        throw new Exception("Failed to update phone number: " . $stmtPhone->error);
    }
    $phoneAffectedRows = $stmtPhone->affected_rows;

    // ตรวจสอบว่ามีการอัปเดตจริงหรือไม่
    if ($tutorAffectedRows === 0 && $phoneAffectedRows === 0) {
        throw new Exception("No changes were made to the data");
    }

    // Commit transaction
    $conn->commit();
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Data updated successfully",
        "affected" => [
            "tutor" => $tutorAffectedRows,
            "phone" => $phoneAffectedRows
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

// ปิดการเชื่อมต่อ
if (isset($stmt)) $stmt->close();
if (isset($stmtPhone)) $stmtPhone->close();
$conn->close();
?>