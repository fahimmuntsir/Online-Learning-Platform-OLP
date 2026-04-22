<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['userId'])) {
    // If not logged in, redirect to the login page
    header("Location: login.html");
    exit();
}

// Database connection parameters
$servername = "localhost";
$username = "root"; // Adjust if you have a different username
$password = ""; // Adjust if you have a password
$dbname = "learning_platform";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the course ID from the URL
$course_id = $_GET['Course_ID'];

// Fetch course details from the database
$query = "SELECT c.CourseName, c.Search_ID, c.Description, u.First_Name AS instructor_first_name, u.Last_Name AS instructor_last_name
          FROM courses c
          JOIN Userinfo u ON c.Ins_ID = u.UserID
          WHERE c.Course_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $course = $result->fetch_assoc();
} else {
    echo "Course not found.";
    exit();
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Details</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="content">
            <h1>Course Details</h1>
            <div class="course-card">
                <h2><?php echo htmlspecialchars($course['CourseName']); ?></h2>
                <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_first_name']) . ' ' . htmlspecialchars($course['instructor_last_name']); ?></p>
                <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($course['Description'])); ?></p>
                <p><strong>Search ID:</strong> <?php echo htmlspecialchars($course['Search_ID']); ?></p>
            </div>
        </main>
    </div>
</body>
</html>
