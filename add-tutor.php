<?php
// ตั้งค่า CORS Headers
header("Access-Control-Allow-Origin: *"); // อนุญาตทุก origin (หรือระบุ http://localhost:4200)
header("Access-Control-Allow-Methods: POST, OPTIONS"); // อนุญาต method POST และ OPTIONS
header("Access-Control-Allow-Headers: Content-Type"); // อนุญาต header Content-Type
header("Content-Type: application/json; charset=UTF-8");

// จัดการ Preflight Request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
error_log("Received data: " . print_r($data, true));

// ตรวจสอบข้อมูลที่ส่งมา
if (!isset($data['tutFName'], $data['tutLName'], $data['tupNumber'])) {
    error_log("Missing fields: " . print_r($data, true));
    echo json_encode(["status" => "error", "message" => "Missing required fields", "received" => $data]);
    exit;
}

// ปรับแต่งและตรวจสอบหมายเลขโทรศัพท์
$tupNumber = preg_replace("/[^0-9]/", "", $data['tupNumber']);
if (!preg_match("/^[0-9]{10}$/", $tupNumber)) {
    echo json_encode(["status" => "error", "message" => "Invalid phone number format (must be 10 digits)"]);
    exit;
}
$data['tupNumber'] = $tupNumber;

$conn = new mysqli("localhost", "root", "", "TutorDB");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

$conn->begin_transaction();

try {
    $stmtPhone = $conn->prepare("INSERT INTO tutphone (tupNumber) VALUES (?)");
    $stmtPhone->bind_param("s", $data['tupNumber']);
    
    if (!$stmtPhone->execute()) {
        throw new Exception("Failed to insert phone number: " . $stmtPhone->error);
    }

    $tupNo = $stmtPhone->insert_id;

    $stmtTutor = $conn->prepare("INSERT INTO tutor (tutFName, tutLName, tupNo) VALUES (?, ?, ?)");
    $stmtTutor->bind_param("ssi", $data['tutFName'], $data['tutLName'], $tupNo);
    
    if (!$stmtTutor->execute()) {
        throw new Exception("Failed to insert tutor: " . $stmtTutor->error);
    }

    $conn->commit();
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Data added successfully"]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$stmtPhone->close();
$stmtTutor->close();
$conn->close();
?>