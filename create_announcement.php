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

// Get logged-in user's ID
$user_id = $_SESSION['userID'];

// Fetch all courses for the logged-in user
$sql = "SELECT Course_ID FROM Course WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row; // Add each course to the array
    }
} else {
    $error_message = "No courses found for the logged-in user.";
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Announcement - OLP</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="announcement-container">
        <h1>Create a New Announcement</h1>

        <!-- Display error message if no courses are found -->
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <!-- Form to create announcement -->
        <form class="announcement-form" method="POST" action="submit_announcement.php">
            <label for="course-select">Select Course:</label>
            <select id="course-select" name="Course_ID" required>
                <option value="">Choose a Course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo htmlspecialchars($course['Course_ID']); ?>">
                        <?php echo htmlspecialchars($course['Course_ID']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="announcement-title">Announcement Title:</label>
            <input type="text" id="announcement-title" name="Title" placeholder="Enter the title of your announcement" required>

            <label for="announcement-content">Announcement Content:</label>
            <textarea id="announcement-content" name="Content" placeholder="Enter the content of your announcement" rows="6" required></textarea>

            <button type="submit">Post Announcement</button>
        </form>

        <a href="instructor_dashboard.html"><button class="back-btn">Back to Dashboard</button></a>
    </div>
</body>
</html>
