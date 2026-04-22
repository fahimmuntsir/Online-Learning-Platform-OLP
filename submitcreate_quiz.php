<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "learning_platform");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$course_id = $_POST['Course_ID'];
$quiz_no = $_POST['Quiz_NO'];
$description = $_POST['Description_Quiz'];
$questions = $_POST['questions'];
$quiz_date = $_POST['Quiz_Date'] ?? null;
$quiz_time = $_POST['Quiz_Time'] ?? null;

// Add date/time columns if not exist
$conn->query("ALTER TABLE quiz_description ADD COLUMN IF NOT EXISTS Quiz_Date DATE DEFAULT NULL");
$conn->query("ALTER TABLE quiz_description ADD COLUMN IF NOT EXISTS Quiz_Time TIME DEFAULT NULL");

// Check if same Course_ID + Quiz_NO already exists
$sql = "SELECT * FROM Quiz_Description WHERE Course_ID = ? AND Quiz_NO = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $course_id, $quiz_no);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    // Redirect back to create_quiz with error message
    header("Location: create_quiz.php?error=duplicate&course=" . urlencode($course_id) . "&quiz_no=" . urlencode($quiz_no));
    exit();
}
$stmt->close();

// Insert into Quiz_Description table
$sql = "INSERT INTO Quiz_Description (Quiz_NO, Course_ID, Description_Quiz, Quiz_Date, Quiz_Time) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issss", $quiz_no, $course_id, $description, $quiz_date, $quiz_time);
if (!$stmt->execute()) {
    die("Error inserting into Quiz_Description: " . $stmt->error);
}
$stmt->close();

// Insert questions into quiz_question table
foreach ($questions as $que_no => $question) {
    $sql = "INSERT INTO quiz_question (Course_ID, Quiz_NO, Que_NO, Question, Choice1, Choice2, Choice3, Choice4, Correct_Ans) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "siissssss",
        $course_id,
        $quiz_no,
        $que_no,
        $question['text'],
        $question['choice1'],
        $question['choice2'],
        $question['choice3'],
        $question['choice4'],
        $question['correct']
    );
    if (!$stmt->execute()) {
        die("Error inserting into quiz_question: " . $stmt->error);
    }
    $stmt->close();
}

// Redirect back to dashboard with success message
header("Location: instructor_dashboard.php?success=1");
$conn->close();
?>