<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$input = json_decode(file_get_contents('php://input'), true);
$couCode = isset($input['couCode']) ? (int)$input['couCode'] : null;
$tutCode = isset($input['tutCode']) ? (int)$input['tutCode'] : null;

if (!$couCode || !$tutCode) {
    echo json_encode(['success' => false, 'error' => 'กรุณาระบุรหัสคอร์สและรหัสผู้สอน']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "tutordb");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$stmt = $conn->prepare('INSERT INTO opencourse (couCode, tutCode) VALUES (?, ?)');
$stmt->bind_param('ii', $couCode, $tutCode);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'เพิ่มตารางการเปิดคอร์สสำเร็จ']);
} else {
    echo json_encode(['success' => false, 'error' => 'ไม่สามารถเพิ่มตารางการเปิดคอร์สได้: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>