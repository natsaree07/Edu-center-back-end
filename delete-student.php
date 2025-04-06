<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_GET['stdCode'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "รหัสนักเรียนไม่ถูกต้อง"]);
        exit;
    }

    $stdCode = intval($_GET['stdCode']);

    $conn = new mysqli("localhost", "root", "", "TutorDB");
    $conn->set_charset("utf8");

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "เชื่อมต่อฐานข้อมูลล้มเหลว"]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM student WHERE stdCode = ?");
    $stmt->bind_param("i", $stdCode);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "ลบข้อมูลนักเรียนเรียบร้อย"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "ไม่สามารถลบข้อมูลได้: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
}
