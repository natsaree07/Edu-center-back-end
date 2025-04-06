<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// เปิด error reporting ชั่วคราวเพื่อ debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "tutordb");

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$result = $conn->query('SELECT opcCode, couCode, tutCode FROM opencourse');
$opencourses = [];

if ($result === false) {
    echo json_encode(['status' => 'error', 'error' => 'Query failed: ' . $conn->error]);
    exit;
}

while ($row = $result->fetch_assoc()) {
    $opencourses[] = [
        'opcCode' => (int)$row['opcCode'],
        'couCode' => (int)$row['couCode'],
        'tutCode' => (int)$row['tutCode']
    ];
}

echo json_encode(['status' => 'success', 'data' => $opencourses]);
$conn->close();
?>