<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

$userID = $_SESSION['userID'];
$conn = new mysqli("localhost", "root", "", "learning_platform");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get course data from form submission
    $courseID = $_POST['Course_ID'];
    $courseName = $_POST['CourseName'];
    $description = $_POST['Description'];
    $startDate = $_POST['Start_Date'];
    $endDate = $_POST['End_Date'];

    // Check if the course belongs to the logged-in user
    $checkSQL = "SELECT * FROM Course WHERE Course_ID = ? AND UserID = ?";
    $checkStmt = $conn->prepare($checkSQL);
    $checkStmt->bind_param("ss", $courseID, $userID);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    // If no rows are found, user doesn't own this course
    if ($checkResult->num_rows === 0) {
        echo "Error: You do not have permission to update this course.";
        exit();
    }

    // Update the course details (only for the course with the matching Course_ID)
    $updateSQL = "UPDATE Course SET CourseName = ?, Description = ?, Start_Date = ?, End_Date = ? WHERE Course_ID = ? AND UserID = ?";
    $updateStmt = $conn->prepare($updateSQL);
    $updateStmt->bind_param("ssssss", $courseName, $description, $startDate, $endDate, $courseID, $userID); // Make sure to include UserID for additional validation

    if ($updateStmt->execute()) {
        echo "Course updated successfully!";
        header("Location: view_course.php");  // Redirect after successful update
        exit();
    } else {
        echo "Error: " . $updateStmt->error;
    }

    $updateStmt->close();
}

$conn->close();
?>
