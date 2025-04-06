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

if (!isset($input['couCode']) || !isset($input['cteCode']) || !isset($input['deCode']) || 
    !isset($input['schCode']) || !isset($input['crfCode']) || !isset($input['plcCode']) || 
    !isset($input['branch'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$couCode = $input['couCode'];
$cteCode = $input['cteCode'];
$deCode = $input['deCode'];
$schCode = $input['schCode'];
$crfCode = $input['crfCode'];
$plcCode = $input['plcCode'];
$branch = $input['branch'];
$image_id = isset($input['image_id']) ? $input['image_id'] : null;

$sql = "UPDATE course SET cteCode = ?, deCode = ?, schCode = ?, crfCode = ?, plcCode = ?, branch = ?, image_id = ? 
        WHERE couCode = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiisii", $cteCode, $deCode, $schCode, $crfCode, $plcCode, $branch, $image_id, $couCode);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'แก้ไขคอร์สสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'error' => 'ไม่พบคอร์สที่ต้องการแก้ไข']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>