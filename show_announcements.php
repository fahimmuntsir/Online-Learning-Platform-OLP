<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "learning_platform");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the StudentID for the logged-in user
$userID = $_SESSION['userID'];
$studentQuery = "SELECT StudentID FROM Student WHERE UserID = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $studentID = $student['StudentID'];

    // Fetch announcements for courses the student is enrolled in
    $announcementQuery = "
        SELECT 
            Announcement.Course_ID, 
            Announcement.Title, 
            Announcement.Content, 
            Announcement.Created_At 
        FROM 
            Announcement 
        INNER JOIN 
            Enrollment 
        ON 
            Announcement.Course_ID = Enrollment.course_ID 
        WHERE 
            Enrollment.StudentID = ?
        ORDER BY 
            Announcement.Created_At DESC";
    $stmt = $conn->prepare($announcementQuery);
    $stmt->bind_param("i", $studentID);
    $stmt->execute();
    $announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $announcements = []; // No enrolled courses
}

// Output announcements as JSON
header('Content-Type: application/json');
echo json_encode($announcements);

$conn->close();
?>
