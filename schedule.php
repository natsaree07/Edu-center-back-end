<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // ใช้ * ชั่วคราวใน development

error_reporting(E_ALL);
ini_set('display_errors', 1); // เปิดชั่วคราวเพื่อ debug
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

function sendError($message) {
    exit(json_encode(["error" => $message], JSON_UNESCAPED_UNICODE));
}

$conn = new mysqli("localhost", "root", "", "tutordb");
$conn->set_charset("utf8");
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5); // ตั้ง timeout 5 วินาที

if ($conn->connect_error) {
    sendError("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$stdCode = isset($_GET['stdCode']) ? (int)$_GET['stdCode'] : 0;
if ($stdCode <= 0) {
    sendError("รหัสนักเรียนไม่ถูกต้อง");
}

$sql = "
    SELECT 
        ct.cteName AS course_name,
        d.deDescrip AS course_desc,
        CONCAT(sch.schDay, ' ', sch.schStart, ' - ', sch.schEnd) AS schedule,
        CONCAT(COALESCE(std.stdFName, 'ไม่ระบุ'), ' ', COALESCE(std.stdLName, '')) AS student_name,
        CONCAT(COALESCE(tut.tutFName, 'ไม่ระบุ'), ' ', COALESCE(tut.tutLName, '')) AS tutor_name,
        tp.tupNumber AS tutor_phone,
        p.plcName AS location
    FROM enrollment e
    JOIN student std ON e.stdCode = std.stdCode
    JOIN opencourse o ON e.opcCode = o.opcCode
    JOIN course c ON o.couCode = c.couCode
    JOIN courseteaching ct ON c.cteCode = ct.cteCode
    JOIN detail d ON c.deCode = d.deCode
    JOIN availableSchedule sch ON c.schCode = sch.schCode
    JOIN tutor tut ON o.tutCode = tut.tutCode
    JOIN tutphone tp ON tut.tupNo = tp.tupNo
    JOIN place p ON c.plcCode = p.plcCode
    WHERE e.stdCode = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    sendError("เกิดข้อผิดพลาดในการเตรียมคำสั่ง: " . $conn->error);
}

$stmt->bind_param("i", $stdCode);
if (!$stmt->execute()) {
    sendError("เกิดข้อผิดพลาดในการรันคำสั่ง: " . $stmt->error);
}

$result = $stmt->get_result();
$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["error" => "ไม่พบข้อมูลการลงทะเบียนสำหรับรหัสนักเรียน = $stdCode"], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>