<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tutordb";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT 
                opencourse.opcCode,
                courseteaching.cteName, 
                Detail.deDescrip, 
                availableSchedule.schDay, 
                availableSchedule.schStart, 
                availableSchedule.schEnd, 
                tutor.tutFName, 
                tutor.tutLName, 
                CourseRegistrationFees.crfFee,
                CourseRegistrationFees.crfCode,
                course.couCode,
                tutor.tutCode,
                place.plcName,
                course.image_id,
                images.image_url
            FROM course
            LEFT JOIN opencourse ON course.couCode = opencourse.couCode
            LEFT JOIN tutor ON opencourse.tutCode = tutor.tutCode
            LEFT JOIN courseteaching ON course.cteCode = courseteaching.cteCode
            LEFT JOIN Detail ON course.deCode = Detail.deCode
            LEFT JOIN availableSchedule ON course.schCode = availableSchedule.schCode
            LEFT JOIN CourseRegistrationFees ON course.crfCode = CourseRegistrationFees.crfCode
            LEFT JOIN place ON course.plcCode = place.plcCode
            LEFT JOIN images ON course.image_id = images.image_id";

    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $data = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cteName = strtolower($row['cteName']);

            if (
                strpos($cteName, 'circuit') !== false ||
                strpos($cteName, 'analysis') !== false ||
                strpos($cteName, 'power') !== false
            ) {
                $row['branch'] = 'ee';
            }
            elseif (
                strpos($cteName, 'vectors') !== false ||
                strpos($cteName, 'computer') !== false ||
                strpos($cteName, 'program') !== false ||
                strpos($cteName, 'com') !== false
            ) {
                $row['branch'] = 'ce';
            } 
            elseif (
                strpos($cteName, 'เครื่องกล') !== false ||
                strpos($cteName, 'strength') !== false
            ) {
                $row['branch'] = 'me';
            } 
            elseif (
                strpos($cteName, 'mechanics') !== false ||
                strpos($cteName, 'thermo') !== false
            ) {
                $row['branch'] = 'civil';
            } 
            else {
                $row['branch'] = 'other';
            }

            $data[] = $row;
        }
    }

    $response = [
        "status" => "success",
        "data" => $data,
        "message" => $data ? "" : "ไม่มีข้อมูลในระบบ"
    ];
    echo json_encode($response);

} catch (Exception $e) {
    $response = [
        "status" => "error",
        "data" => [],
        "message" => $e->getMessage()
    ];
    http_response_code(500);
    echo json_encode($response);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>