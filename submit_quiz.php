<?php
session_start();
if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$score = $_POST['score']; 
$course_id = $_POST['course_id'];
$quiz_no = $_POST['quiz_no'];
$user_id = $_SESSION['userID'];

// Fetch student ID
$query = "SELECT StudentID FROM Student WHERE UserID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_id = $student['StudentID'];

// Check if the student has already submitted this quiz
$query = "SELECT 1 FROM quiz_result WHERE Course_ID = ? AND Quiz_NO = ? AND Student_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sis", $course_id, $quiz_no, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // If quiz already taken, show message and provide button to go back to available quizzes
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Quiz Already Taken</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <h1>You Have Already Taken This Quiz</h1>
            <p>Sorry, you cannot submit the same quiz multiple times.</p>
            <button onclick="window.location.href=\'available_quizes.php\'">Go to Available Quizzes</button>
        </div>
    </body>
    </html>';
    exit();
}

// Fetch course name
$query = "SELECT CourseName FROM Course WHERE Course_ID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();
$course_name = $course['CourseName'];

// Insert the quiz result into the database
$query = "INSERT INTO quiz_result (Course_ID, Quiz_NO, Score, Student_ID, CourseName) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("siiss", $course_id, $quiz_no, $score, $student_id, $course_name);
$stmt->execute();

// Close connections
$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Submission</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Quiz Submitted Successfully</h1>
        <p>Thank you for completing the quiz. Your submission has been recorded.</p>
        <button onclick="window.location.href='student_dashboard.html'">Go to Dashboard</button>
    </div>
</body>
</html>
