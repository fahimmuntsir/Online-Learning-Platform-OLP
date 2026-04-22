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

$courseID = $_GET['course_id']; // Get the course ID from the query string

// Query to fetch the Course details (Course_ID and Description)
$courseQuery = "SELECT Course_ID, CourseName, Description FROM Course WHERE Course_ID = ?";
$stmt = $conn->prepare($courseQuery);
$stmt->bind_param("s", $courseID);  // Assuming Course_ID is a string (varchar)
$stmt->execute();
$courseResult = $stmt->get_result();

if ($courseResult->num_rows > 0) {
    $course = $courseResult->fetch_assoc();
} else {
    echo "Course not found.";
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Modules - OLP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <main class="content">
            <section id="course-details">
                <h1>Course Details</h1>
                <div class="course-card">
                    <h2>Course Name: <?php echo htmlspecialchars($course['CourseName']); ?></h2>
                    <p><strong>Course ID:</strong> <?php echo htmlspecialchars($course['Course_ID']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($course['Description']); ?></p>
                </div>
            </section>

            <!-- Back to Dashboard Button -->
            <div class="back-btn-container">
                <a href="show_courses.html">
                    <button class="back-btn"><i class="fas fa-arrow-left"></i> Back to Courses</button>
                </a>
            </div>
        </main>
    </div>
</body>
</html>
