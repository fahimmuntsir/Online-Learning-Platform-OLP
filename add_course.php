<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learning_platform";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['Course_ID'];
    $course_name = $_POST['CourseName'];
    $description = $_POST['Description'];
    $start_date = $_POST['Start_Date'];
    $end_date = $_POST['End_Date'];
    $user_id = $_SESSION['userID'];

    if (empty($course_id) || empty($course_name) || empty($description) || empty($start_date) || empty($end_date)) {
        echo "Error: All fields are required.";
    } else {
        $conn->begin_transaction();
        try {
            $sql = "INSERT INTO Course (Course_ID, CourseName, Description, UserID, Start_Date, End_Date) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $course_id, $course_name, $description, $user_id, $start_date, $end_date);
            $stmt->execute();
            $conn->commit();
            echo "New course added successfully!";
            header("Location: instructor_dashboard.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo "Error: " . $e->getMessage();
        }
    }
}
$conn->close();
?>