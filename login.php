<?php
session_start();
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $content = file_get_contents('php://input');
    error_log("Received: " . $content);
    $dataFrm = json_decode($content, true);

    if (isset($dataFrm['regUser']) && isset($dataFrm['regPassword'])) {
        $regUser = $dataFrm['regUser'];
        $regPassword = $dataFrm['regPassword'];

        $hostAuth = "localhost";
        $userAuth = "root";
        $passAuth = "";
        $database = "tutordb";

        $link = new mysqli($hostAuth, $userAuth, $passAuth, $database);
        if ($link->connect_error) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $link->connect_error]);
            exit();
        }

        mysqli_set_charset($link, "utf8mb4");

        // ===== เช็คจากตาราง admin =====
        $stmtAdmin = $link->prepare("SELECT password, name, role FROM admin WHERE username = ?");
        $stmtAdmin->bind_param("s", $regUser);
        $stmtAdmin->execute();
        $stmtAdmin->store_result();

        if ($stmtAdmin->num_rows > 0) {
            $stmtAdmin->bind_result($hashedPassword, $name, $role);
            $stmtAdmin->fetch();

            if ($hashedPassword && password_verify($regPassword, $hashedPassword)) {
                $_SESSION['regUser'] = $regUser;
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'type' => 'admin',
                    'message' => 'Admin login successful',
                    'fullName' => $name,
                    'role' => $role
                ]);
                $stmtAdmin->close();
                $link->close();
                exit();
            } else {
                $stmtAdmin->close(); // ไปเช็ค student ต่อ
            }
        } else {
            $stmtAdmin->close();
        }

        // ===== เช็คจากตารางนักเรียน =====
        $stmt = $link->prepare("SELECT r.regPassword, s.stdFName, s.stdLName 
                                FROM registration r 
                                JOIN student s ON r.stdCode = s.stdCode 
                                WHERE r.regUser = ?");
        $stmt->bind_param("s", $regUser);
        $stmt->execute();
        $stmt->bind_result($hashedPassword, $stdFName, $stdLName);
        $stmt->fetch();

        if ($hashedPassword && password_verify($regPassword, $hashedPassword)) {
            $_SESSION['regUser'] = $regUser;
            $fullName = "$stdFName $stdLName";
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'type' => 'student',
                'message' => 'Student login successful',
                'fullName' => $fullName
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
        }

        $stmt->close();
        $link->close();
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
