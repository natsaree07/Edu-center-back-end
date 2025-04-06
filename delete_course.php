<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tutordb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['couCode'])) {
    echo json_encode(['success' => false, 'error' => 'Missing couCode']);
    exit();
}

$couCode = $input['couCode'];

$sql = "DELETE FROM course WHERE couCode = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $couCode);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'ลบคอร์สสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'error' => 'ไม่พบคอร์สที่ต้องการลบ']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>