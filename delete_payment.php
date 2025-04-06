<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");


if (!isset($_GET['payID'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing payID']);
  exit;
}

$conn = new mysqli("localhost", "root", "", "tutordb");
$conn->set_charset("utf8");

$payID = intval($_GET['payID']);
$stmt = $conn->prepare("DELETE FROM payment WHERE payID = ?");
$stmt->bind_param("i", $payID);

if ($stmt->execute()) {
  echo json_encode(['message' => 'ลบสำเร็จ']);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'ลบไม่สำเร็จ']);
}
