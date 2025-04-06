<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// การตั้งค่าฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tutordb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["error" => "การเชื่อมต่อล้มเหลว: " . $conn->connect_error]));
}
$conn->set_charset("utf8");

// ฟังก์ชันตรวจสอบ chCode
function validateChannel($conn, $chCode) {
    $stmt = $conn->prepare("SELECT chCode FROM channel WHERE chCode = ?");
    $stmt->bind_param("i", $chCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->num_rows > 0;
}

// ✅ ฟังก์ชันตรวจสอบ crfCode
function validateCrfCode($conn, $crfCode) {
    $stmt = $conn->prepare("SELECT crfCode FROM courseregistrationfees WHERE crfCode = ?");
    $stmt->bind_param("i", $crfCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->num_rows > 0;
}

// ฟังก์ชันจัดการการอัปโหลดไฟล์
function uploadSlipFile($file) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = $file['name'] ?? '';
    if (empty($fileName)) {
        return '';
    }

    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $fileSize = $file['size'];
    $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];

    // ตรวจสอบ MIME type เพิ่มเติม
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

    if (!in_array($fileType, $allowedTypes) || !in_array($mimeType, $allowedMimeTypes)) {
        throw new Exception("ประเภทไฟล์ไม่ถูกต้อง! อนุญาตเฉพาะ jpg, png, pdf");
    }

    if ($fileSize > 5000000) {
        throw new Exception("ไฟล์ใหญ่เกินไป! ขนาดสูงสุด 5MB");
    }

    $newFileName = uniqid() . '.' . $fileType;
    $targetFile = $targetDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return $newFileName;
    } else {
        throw new Exception("ไม่สามารถอัปโหลดไฟล์ได้");
    }
}

// -------------------- GET: โหลดช่องทางชำระเงิน --------------------
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        $stmt = $conn->prepare("SELECT chCode, chName FROM channel");
        $stmt->execute();
        $result = $stmt->get_result();

        $channels = [];
        while ($row = $result->fetch_assoc()) {
            $channels[] = [
                "chCode" => $row["chCode"],
                "chName" => $row["chName"]
            ];
        }

        echo json_encode($channels);
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// -------------------- POST: บันทึกการชำระเงิน --------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $payDate = $_POST['payDate'] ?? '';
        $payTime = $_POST['payTime'] ?? '';
        $chCode = intval($_POST['chCode'] ?? 0);
        $address = $_POST['address'] ?? '';
        $cartItemsJson = $_POST['cartItems'] ?? '';
        $slipFile = $_FILES['slipFile'] ?? null;

        if (empty($payDate) || empty($payTime) || empty($chCode) || empty($cartItemsJson)) {
            throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน!");
        }

        if (!validateChannel($conn, $chCode)) {
            throw new Exception("รหัสธนาคารไม่ถูกต้อง!");
        }

        $cartItems = json_decode($cartItemsJson, true);
        if (!is_array($cartItems)) {
            throw new Exception("รูปแบบข้อมูลสินค้าไม่ถูกต้อง!");
        }

        $slipFileName = '';
        if ($slipFile) {
            $slipFileName = uploadSlipFile($slipFile);
        }

        $conn->begin_transaction();
        $stmt = $conn->prepare("INSERT INTO payment (payDate, payCount, payTime, crfCode, chCode, couCode, slipFile, address) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($cartItems as $item) {
            $price = floatval($item['price'] ?? 0);
            $discount = floatval($item['discount'] ?? 0);
            $payCount = $price * ((100 - $discount) / 100);
            $crfCode = intval($item['crfCode'] ?? 0);
            $couCode = intval($item['couCode'] ?? 0);

            if ($payCount <= 0 || $crfCode === 0 || $couCode === 0) {
                throw new Exception("ข้อมูลสินค้าไม่สมบูรณ์!");
            }

            // ✅ ตรวจสอบว่า crfCode มีอยู่จริง
            if (!validateCrfCode($conn, $crfCode)) {
                throw new Exception("crfCode $crfCode ไม่มีอยู่ในฐานข้อมูล!");
            }

            $stmt->bind_param("sdsiiiss", $payDate, $payCount, $payTime, $crfCode, $chCode, $couCode, $slipFileName, $address);
            if (!$stmt->execute()) {
                throw new Exception("เกิดข้อผิดพลาดในการบันทึก: " . $stmt->error);
            }
        }

        $conn->commit();
        echo json_encode(["message" => "เพิ่มข้อมูลสำเร็จ!"]);
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["error" => $e->getMessage()]);
    }
}

$conn->close();
?>
