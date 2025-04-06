<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

ini_set('display_errors', 1);
error_reporting(E_ALL);

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "TutorDB");
$conn->set_charset("utf8mb4"); // ปรับเป็น utf8mb4 เพื่อรองรับอักขระพิเศษ

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// JOIN ข้อมูลจากตารางที่เกี่ยวข้องทั้งหมด
$sql = "
SELECT 
    s.stdCode,
    s.stdFName,
    s.stdLName,
    s.stdFaculty,
    s.stdMajor,
    s.stdDOB,
    s.stdEmail,
    -- ที่อยู่
    a.addPmo,
    a.addSoy,
    a.addRoad,
    a.addNO,
    -- ตำบล อำเภอ จังหวัด
    sub.sbdName,
    dis.disName,
    pro.proName,
    dis.PostCode,
    -- เบอร์โทร
    ph.stdNumber,
    -- ช่องทางติดต่อ
    ct.conName
FROM student s
LEFT JOIN addr a ON s.addCode = a.addCode
LEFT JOIN subdistrict sub ON a.sbdCode = sub.sbdCode
LEFT JOIN district dis ON sub.disCode = dis.disCode
LEFT JOIN province pro ON dis.proCode = pro.proCode
LEFT JOIN stdphone ph ON s.stpNo = ph.stpNo
LEFT JOIN contactType ct ON s.conCode = ct.conCode
";

$result = $conn->query($sql);

if ($result === false) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Query ผิดพลาด: " . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// ส่ง response ในรูปแบบที่ Angular คาดหวัง
echo json_encode([
    "status" => "success",
    "data" => $data,
    "message" => $data ? "" : "ไม่มีข้อมูลในระบบ"
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$conn->close();
?>