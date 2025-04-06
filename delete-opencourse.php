<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$input = json_decode(file_get_contents('php://input'), true);
$opcCode = isset($input['opcCode']) ? (int)$input['opcCode'] : null;

if (!$opcCode) {
    echo json_encode(['success' => false, 'error' => 'กรุณาระบุรหัสตารางการเปิดคอร์ส']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "tutordb");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$stmt = $conn->prepare('DELETE FROM opencourse WHERE opcCode = ?');
$stmt->bind_param('i', $opcCode);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'ลบตารางการเปิดคอร์สสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'error' => 'ไม่พบตารางการเปิดคอร์สที่ต้องการลบ']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ไม่สามารถลบตารางการเปิดคอร์สได้: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>