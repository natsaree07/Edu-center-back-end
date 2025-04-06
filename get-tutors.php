<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$conn = new mysqli("localhost", "root", "", "TutorDB");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// ใช้ LEFT JOIN เพื่อดึงข้อมูลจากทั้ง tutor และ tutphone
$sql = "
    SELECT t1.tutCode, t1.tutFName, t1.tutLName, t1.tupNo, t2.tupNumber 
    FROM tutor AS t1
    LEFT JOIN tutphone AS t2 ON t1.tupNo = t2.tupNo
";

$result = $conn->query($sql);

if (!$result) {
    error_log("SQL query failed: " . $conn->error);
    http_response_code(500);
    echo json_encode(["error" => "SQL query failed: " . $conn->error]);
    exit;
}

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    http_response_code(200);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(["message" => "No data found"]);
}

$conn->close();
?>
