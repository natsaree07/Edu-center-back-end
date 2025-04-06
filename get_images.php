<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// การเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tutordb";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        throw new Exception("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
    }

    $sql = "SELECT image_id, image_url, image_name FROM images ORDER BY image_id DESC";
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("การดึงข้อมูลล้มเหลว: " . $conn->error);
    }

    $images = [];

    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $images
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
