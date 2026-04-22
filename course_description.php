<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Description - OLP</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="max-width: 800px; margin: 0 auto; text-align: center;">
        <h1>Course Description</h1>

        <?php
        session_start(); // Start the session

        // Check if the user is logged in
        if (!isset($_SESSION['userID'])) {
            header("Location: login.html");
            exit();
        }

        $userID = $_SESSION['userID']; // Get the logged-in user's ID
        $courseID = isset($_GET['course_id']) ? $_GET['course_id'] : null; // Get the course ID from the URL

        if ($courseID === null) {
            echo "<p>No course selected.</p>";
            exit();
        }

        $conn = new mysqli("localhost", "root", "", "learning_platform");

        // Check database connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Fetch course details including the description
        $sql = "SELECT * FROM Course WHERE Course_ID = ? AND UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $courseID, $userID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $course = $result->fetch_assoc();
            echo "<h2>" . htmlspecialchars($course['CourseName']) . "</h2>";
            echo "<p><strong>Description:</strong></p>";
            echo "<p>" . htmlspecialchars($course['Description']) . "</p>";
            echo "<p><strong>Start Date:</strong> " . htmlspecialchars($course['Start_Date']) . "</p>";
            echo "<p><strong>End Date:</strong> " . htmlspecialchars($course['End_Date']) . "</p>";
        } else {
            echo "<p>Course not found or you do not have permission to view this course.</p>";
        }

        $stmt->close();
        $conn->close();
        ?>

        <!-- Back Button -->
        <a href="view_course.php">
            <button style="margin-top: 20px; padding: 10px 20px;">Back to View Courses</button>
        </a>
    </div>
</body>
</html>
