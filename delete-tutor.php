<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed, use DELETE"]);
    ob_end_flush();
    exit;
}

$tutCode = filter_input(INPUT_GET, 'tutCode', FILTER_VALIDATE_INT);
if ($tutCode === false || $tutCode === null) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid or missing tutCode"]);
    ob_end_flush();
    exit;
}
error_log("Received tutCode: " . $tutCode);

$conn = new mysqli("localhost", "root", "", "TutorDB");
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    ob_end_flush();
    exit;
}
$conn->set_charset("utf8");

$checkStmt = null;
$stmtPhone = null;
$stmtTutor = null;

try {
    $conn->begin_transaction();

    $checkStmt = $conn->prepare("SELECT tupNo FROM tutor WHERE tutCode = ?");
    if (!$checkStmt) {
        throw new Exception("Prepare check failed: " . $conn->error);
    }
    $checkStmt->bind_param("i", $tutCode);
    if (!$checkStmt->execute()) {
        throw new Exception("Execute check failed: " . $checkStmt->error);
    }
    $result = $checkStmt->get_result();
    if ($result->num_rows === 0) {
        $conn->rollback();
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "No tutor found with tutCode: $tutCode"]);
        ob_end_flush();
        exit;
    }
    $tupNo = $result->fetch_assoc()['tupNo'];
    error_log("Found tupNo: " . $tupNo);

    $stmtPhone = $conn->prepare("DELETE FROM tutphone WHERE tupNo = ?");
    if (!$stmtPhone) {
        throw new Exception("Prepare tutphone delete failed: " . $conn->error);
    }
    $stmtPhone->bind_param("i", $tupNo);
    if (!$stmtPhone->execute()) {
        throw new Exception("Execute tutphone delete failed: " . $stmtPhone->error);
    }
    $phoneRows = $stmtPhone->affected_rows;
    error_log("Deleted $phoneRows rows from tutphone");

    $stmtTutor = $conn->prepare("DELETE FROM tutor WHERE tutCode = ?");
    if (!$stmtTutor) {
        throw new Exception("Prepare tutor delete failed: " . $conn->error);
    }
    $stmtTutor->bind_param("i", $tutCode);
    if (!$stmtTutor->execute()) {
        throw new Exception("Execute tutor delete failed: " . $stmtTutor->error);
    }
    $tutorRows = $stmtTutor->affected_rows;
    error_log("Deleted $tutorRows rows from tutor");

    if ($tutorRows > 0 || $phoneRows > 0) {
        $conn->commit();
        http_response_code(200);
        $response = [
            "status" => "success",
            "message" => "ลบข้อมูลติวเตอร์สำเร็จ",
            "deleted" => ["tutor" => $tutorRows, "phone" => $phoneRows]
        ];
    } else {
        $conn->rollback();
        http_response_code(200);
        $response = [
            "status" => "success",
            "message" => "ไม่มีข้อมูลถูกลบสำหรับ tutCode: $tutCode",
            "deleted" => ["tutor" => 0, "phone" => 0]
        ];
    }

    echo json_encode($response);
    ob_end_flush();
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    ob_end_flush();
} finally {
    // ปิด statement และ connection อย่างปลอดภัย
    if ($checkStmt && !$checkStmt->errno) $checkStmt->close();
    if ($stmtPhone && !$stmtPhone->errno) $stmtPhone->close();
    if ($stmtTutor && !$stmtTutor->errno) $stmtTutor->close();
    if ($conn && !$conn->connect_error) $conn->close();
}
?>