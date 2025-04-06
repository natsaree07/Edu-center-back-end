<?php
session_start(); // เริ่ม session เพื่อตรวจสอบผู้ใช้ที่ล็อกอิน

header('Content-Type: text/html; charset=utf-8'); // กำหนด charset

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือยัง
if (!isset($_SESSION['regUser'])) {
    $fullName = "กรุณาล็อกอิน";
} else {
    $regUser = $_SESSION['regUser'];

    // เชื่อมต่อฐานข้อมูล
    $link = new mysqli("localhost", "root", "", "tutordb");
    if ($link->connect_error) {
        die("Connection failed: " . $link->connect_error);
    }
    mysqli_set_charset($link, "utf8mb4");

    // ดึงชื่อและนามสกุลจาก registration และ student
    $stmt = $link->prepare("SELECT s.stdFName, s.stdLName 
                            FROM registration r 
                            JOIN student s ON r.stdCode = s.stdCode 
                            WHERE r.regUser = ?");
    $stmt->bind_param("s", $regUser);
    $stmt->execute();
    $stmt->bind_result($stdFName, $stdLName);
    $stmt->fetch();

    // รวมชื่อและนามสกุล
    $fullName = $stdFName && $stdLName ? "$stdFName $stdLName" : "ไม่พบข้อมูล";
    
    $stmt->close();
    $link->close();
}
?>