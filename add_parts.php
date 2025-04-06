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
    die(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
}

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $cteName = $data['cteName'];
    $deDescrip = $data['deDescrip'];
    $plcName = $data['plcName'];
    $schDay = $data['schDay'];
    $schStart = $data['schStart'];
    $schEnd = $data['schEnd'];
    $crfFee = $data['crfFee'];
    $imageName = $data['imageName'];
    $imageUrl = $data['imageUrl'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO courseteaching (cteName) VALUES (?)");
        $stmt->bind_param("s", $cteName);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO Detail (deDescrip) VALUES (?)");
        $stmt->bind_param("s", $deDescrip);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO place (plcName) VALUES (?)");
        $stmt->bind_param("s", $plcName);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO availableSchedule (schDay, schStart, schEnd) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $schDay, $schStart, $schEnd);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO CourseRegistrationFees (crfFee) VALUES (?)");
        $stmt->bind_param("d", $crfFee);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO images (image_name, image_url) VALUES (?, ?)");
        $stmt->bind_param("ss", $imageName, $imageUrl);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลสำเร็จ']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีข้อมูลที่ส่งมา']);
}

$conn->close();
?>