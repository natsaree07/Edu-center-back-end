<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// ปิดการแสดงข้อผิดพลาดเป็น HTML
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
    if (!$dataFrm) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูล JSON ไม่ถูกต้อง']);
        exit;
    }

    $required_fields = ['regUser', 'regPassword', 'stdFName', 'stdLName', 'stdFaculty', 'stdMajor', 'stdDOB', 'stdEmail', 
                        'addPmo', 'addSoy', 'addRoad', 'addNO', 'proName', 'disName', 'PostCode', 'stdNumber', 'sbdName', 'conName'];
    $missing_fields = array_filter($required_fields, fn($field) => !isset($dataFrm[$field]) || empty($dataFrm[$field]));
    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ขาดฟิลด์: ' . implode(', ', $missing_fields)]);
        exit;
    }

    try {
        $link = new mysqli("localhost", "root", "", "TutorDB");
        if ($link->connect_error) {
            throw new Exception("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $link->connect_error);
        }
        mysqli_set_charset($link, "utf8mb4");

        $link->begin_transaction();

        $insertProvince = $link->prepare("INSERT INTO province (proName) VALUES (?)");
        if (!$insertProvince) throw new Exception("Prepare failed: " . $link->error);
        $insertProvince->bind_param("s", $dataFrm['proName']);
        if (!$insertProvince->execute()) throw new Exception("Execute failed: " . $insertProvince->error);
        $proCode = $link->insert_id;

        $insertDistrict = $link->prepare("INSERT INTO district (disName, PostCode, proCode) VALUES (?, ?, ?)");
        if (!$insertDistrict) throw new Exception("Prepare failed: " . $link->error);
        $insertDistrict->bind_param("ssi", $dataFrm['disName'], $dataFrm['PostCode'], $proCode);
        if (!$insertDistrict->execute()) throw new Exception("Execute failed: " . $insertDistrict->error);
        $disCode = $link->insert_id;

        $insertSubdistrict = $link->prepare("INSERT INTO subdistrict (sbdName, disCode) VALUES (?, ?)");
        if (!$insertSubdistrict) throw new Exception("Prepare failed: " . $link->error);
        $insertSubdistrict->bind_param("si", $dataFrm['sbdName'], $disCode);
        if (!$insertSubdistrict->execute()) throw new Exception("Execute failed: " . $insertSubdistrict->error);
        $sbdCode = $link->insert_id;

        $insertAddress = $link->prepare("INSERT INTO addr (addPmo, addSoy, addRoad, addNO, sbdCode) VALUES (?, ?, ?, ?, ?)");
        if (!$insertAddress) throw new Exception("Prepare failed: " . $link->error);
        $insertAddress->bind_param("ssssi", $dataFrm['addPmo'], $dataFrm['addSoy'], $dataFrm['addRoad'], $dataFrm['addNO'], $sbdCode);
        if (!$insertAddress->execute()) throw new Exception("Execute failed: " . $insertAddress->error);
        $addCode = $link->insert_id;

        $insertPhone = $link->prepare("INSERT INTO stdphone (stdNumber) VALUES (?)");
        if (!$insertPhone) throw new Exception("Prepare failed: " . $link->error);
        $insertPhone->bind_param("s", $dataFrm['stdNumber']);
        if (!$insertPhone->execute()) throw new Exception("Execute failed: " . $insertPhone->error);
        $stpNo = $link->insert_id;

        $insertContactType = $link->prepare("INSERT INTO contactType (conName) VALUES (?)");
        if (!$insertContactType) throw new Exception("Prepare failed: " . $link->error);
        $insertContactType->bind_param("s", $dataFrm['conName']);
        if (!$insertContactType->execute()) throw new Exception("Execute failed: " . $insertContactType->error);
        $conCode = $link->insert_id;

        $stmt = $link->prepare("INSERT INTO student (stdFName, stdLName, stdFaculty, stdMajor, stdDOB, stdEmail, addCode, stpNo, conCode) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Prepare failed: " . $link->error);
        $stmt->bind_param("ssssssiii", $dataFrm['stdFName'], $dataFrm['stdLName'], $dataFrm['stdFaculty'], $dataFrm['stdMajor'], $dataFrm['stdDOB'], $dataFrm['stdEmail'], $addCode, $stpNo, $conCode);
        if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
        $stdCode = $link->insert_id;

        $insertRegistration = $link->prepare("INSERT INTO registration (regUser, regPassword, stdCode) VALUES (?, ?, ?)");
        if (!$insertRegistration) throw new Exception("Prepare failed: " . $link->error);
        $hashedPassword = password_hash($dataFrm['regPassword'], PASSWORD_DEFAULT);
        $insertRegistration->bind_param("ssi", $dataFrm['regUser'], $hashedPassword, $stdCode);
        if (!$insertRegistration->execute()) throw new Exception("Execute failed: " . $insertRegistration->error);

        $link->commit();
        http_response_code(201);
        echo json_encode(['status' => 'success', 'message' => 'ลงทะเบียนสำเร็จ']);
    } catch (Exception $e) {
        if (isset($link) && $link->errno) {
            $link->rollback();
        }
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'ข้อผิดพลาด: ' . $e->getMessage()]);
        error_log("Error: " . $e->getMessage());
    } finally {
        if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
        if (isset($insertProvince) && $insertProvince instanceof mysqli_stmt) $insertProvince->close();
        if (isset($insertDistrict) && $insertDistrict instanceof mysqli_stmt) $insertDistrict->close();
        if (isset($insertSubdistrict) && $insertSubdistrict instanceof mysqli_stmt) $insertSubdistrict->close();
        if (isset($insertAddress) && $insertAddress instanceof mysqli_stmt) $insertAddress->close();
        if (isset($insertPhone) && $insertPhone instanceof mysqli_stmt) $insertPhone->close();
        if (isset($insertContactType) && $insertContactType instanceof mysqli_stmt) $insertContactType->close();
        if (isset($insertRegistration) && $insertRegistration instanceof mysqli_stmt) $insertRegistration->close();
        if (isset($link) && $link instanceof mysqli) $link->close();
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'ไม่อนุญาตวิธีการนี้']);
}
?>