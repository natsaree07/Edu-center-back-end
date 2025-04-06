<?php
include 'db_connect.php'; // เชื่อมต่อฐานข้อมูล

$response = [];

// ดึงจำนวนคอร์ส
$courseQuery = $conn->query("SELECT COUNT(*) AS total FROM courses");
$response['courses'] = $courseQuery->fetch_assoc()['total'];

// ดึงจำนวนอาจารย์
$teacherQuery = $conn->query("SELECT COUNT(*) AS total FROM teachers");
$response['teachers'] = $teacherQuery->fetch_assoc()['total'];

// ดึงนักเรียนที่สมัครแล้ว
$studentsQuery = $conn->query("SELECT COUNT(*) AS total FROM enrollments WHERE status = 'approved'");
$response['students'] = $studentsQuery->fetch_assoc()['total'];

// ดึงคำขอสมัครที่รออนุมัติ
$pendingQuery = $conn->query("SELECT COUNT(*) AS total FROM enrollments WHERE status = 'pending'");
$response['pending'] = $pendingQuery->fetch_assoc()['total'];

echo json_encode($response);
?>
