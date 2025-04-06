<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ✅ รองรับ preflight request จาก Angular
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tutordb";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
        SELECT 
    s.stdCode,
    s.stdFName,
    s.stdLName,
    o.opcCode,
    ct.cteName,
    av.schDay,
    av.schStart,
    av.schEnd
FROM enrollment e
JOIN student s ON e.stdCode = s.stdCode
JOIN opencourse o ON e.opcCode = o.opcCode
JOIN course c ON o.couCode = c.couCode
JOIN courseteaching ct ON c.cteCode = ct.cteCode
LEFT JOIN availableschedule av ON c.schCode = av.schCode

    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $enrollment = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $enrollment
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "error" => "ไม่สามารถดึงข้อมูลการลงทะเบียนได้: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn = null;
?>
