<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learning_platform";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$course_id = $_POST['Course_ID'];
$title = $_POST['Title'];
$content = $_POST['Content'];

// Insert the announcement into the database
$sql = "INSERT INTO Announcement (Course_ID, UserID, Title, Content) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

// Bind parameters (Course_ID, UserID, Title, Content)
$stmt->bind_param("ssss", $course_id, $_SESSION['userID'], $title, $content);

// Execute the statement
$announcement_posted = false;
$error_message = "";

if ($stmt->execute()) {
    $announcement_posted = true; // Set success flag
} else {
    $error_message = $stmt->error; // Capture error message
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement Posted - OLP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="announcement-result">
        <h1>Announcement Status</h1>

        <!-- Display success or error message -->
        <?php if ($announcement_posted): ?>
            <p>Announcement posted successfully!</p>
        <?php else: ?>
            <p style="color: red;">Error: <?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <!-- Back to Dashboard Button -->
        <a href="instructor_dashboard.html">
            <button style="margin-top: 20px; padding: 10px 20px;">Back to Dashboard</button>
        </a>
    </div>
</body>
</html>
