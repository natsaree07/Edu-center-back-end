<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$conn = new mysqli("localhost", "root", "", "tutordb");
$conn->set_charset("utf8");

$sql = "
SELECT 
  p.*, 
  c.chName AS channelName
FROM payment p
LEFT JOIN channel c ON p.chCode = c.chCode
ORDER BY p.payDate DESC, p.payTime DESC
";

$result = $conn->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

echo json_encode(['data' => $data]);
