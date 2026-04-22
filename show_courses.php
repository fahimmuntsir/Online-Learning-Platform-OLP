<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "learning_platform");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch enrolled courses based on StudentID
$userID = $_SESSION['userID'];
$studentQuery = "SELECT StudentID FROM Student WHERE UserID = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $studentID = $student['StudentID'];

    $enrolledCoursesQuery = "
        SELECT Course.Course_ID, Course.CourseName, Course.Description 
        FROM Course 
        INNER JOIN Enrollment ON Course.Course_ID = Enrollment.course_ID
        WHERE Enrollment.StudentID = ?";
    $stmt = $conn->prepare($enrolledCoursesQuery);
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $courses = [];
}

// Output courses as JSON for the frontend
header('Content-Type: application/json');
echo json_encode($courses);

$conn->close();
?>
