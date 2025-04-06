<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$conn = new mysqli("localhost", "root", "", "tutordb");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(["status" => "error", "error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

$sql = "SELECT crfCode, crfFee FROM courseregistrationfees";
$result = $conn->query($sql);

if (!$result) {
    error_log("SQL query failed: " . $conn->error);
    http_response_code(500);
    echo json_encode(["status" => "error", "error" => "SQL query failed: " . $conn->error]);
    exit;
}

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "crfCode" => (int)$row["crfCode"], // แปลงเป็น integer
            "crfFee" => (float)$row["crfFee"] // แปลงเป็น float
        ];
    }
}

http_response_code(200);
echo json_encode(["status" => "success", "data" => $data], JSON_UNESCAPED_UNICODE);

$conn->close();
?>