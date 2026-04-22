<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

$userID = $_SESSION['userID']; // Get the logged-in user's ID
$conn = new mysqli("localhost", "root", "", "learning_platform");

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch only the courses created by the logged-in user
$courseID = isset($_GET['Course_ID']) ? $_GET['Course_ID'] : null;

if ($courseID) {
    $sql = "SELECT * FROM Course WHERE Course_ID = ? AND UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $courseID, $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error_message = "Error: You do not have permission to edit this course.";
    } else {
        $row = $result->fetch_assoc(); // Fetch course data for editing
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - OLP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Edit Course</h1>

    <!-- Display error message if course not found -->
    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <!-- Form to manually input Course_ID -->
    <?php if (!isset($row)): ?>
        <form method="GET" action="edit_course.php">
            <label for="course-id">Course ID:</label>
            <input type="text" id="course-id" name="Course_ID" value="<?php echo htmlspecialchars($courseID ?? ''); ?>" required>
            <br>
            <button type="submit">Fetch Course</button>
        </form>
    <?php endif; ?>

    <!-- If course is found, display form to edit it -->
    <?php if (isset($row)): ?>
        <form method="POST" action="update_course.php">
            <input type="hidden" name="Course_ID" value="<?php echo htmlspecialchars($row['Course_ID']); ?>">

            <label for="course-name">Course Name:</label>
            <input type="text" id="course-name" name="CourseName" value="<?php echo htmlspecialchars($row['CourseName']); ?>" required>

            <label for="course-description">Description:</label>
            <textarea id="course-description" name="Description" required><?php echo htmlspecialchars($row['Description']); ?></textarea>

            <label for="start-date">Start Date:</label>
            <input type="date" id="start-date" name="Start_Date" value="<?php echo htmlspecialchars($row['Start_Date']); ?>" required>

            <label for="end-date">End Date:</label>
            <input type="date" id="end-date" name="End_Date" value="<?php echo htmlspecialchars($row['End_Date']); ?>" required>

            <button type="submit">Update Course</button>
        </form>
    <?php endif; ?>

    <a href="view_course.php"><button>Back to Courses</button></a>
</body>
</html>
