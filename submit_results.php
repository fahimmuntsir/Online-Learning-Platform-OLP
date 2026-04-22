<?php
session_start();
$conn = new mysqli("localhost", "root", "", "learning_platform");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$courseID = $_POST['Course_ID'] ?? '';
$quizNO   = $_POST['Quiz_NO'] ?? '';

if (empty($courseID) || empty($quizNO)) {
    echo "<script>alert('Please select both a Course and a Quiz.'); window.history.back();</script>";
    exit;
}

// Add is_published column if it doesn't exist yet (safe to run every time)
$conn->query("ALTER TABLE quiz_result ADD COLUMN IF NOT EXISTS is_published TINYINT(1) NOT NULL DEFAULT 0");

// Check if results exist for this course+quiz
$check = $conn->prepare("SELECT COUNT(*) as cnt FROM quiz_result WHERE Course_ID = ? AND Quiz_NO = ?");
$check->bind_param("si", $courseID, $quizNO);
$check->execute();
$row = $check->get_result()->fetch_assoc();

if ($row['cnt'] == 0) {
    echo "<script>alert('No quiz results found for this Course and Quiz. Students must attempt the quiz first.'); window.history.back();</script>";
    exit;
}

// Mark results as published
$stmt = $conn->prepare("UPDATE quiz_result SET is_published = 1 WHERE Course_ID = ? AND Quiz_NO = ?");
$stmt->bind_param("si", $courseID, $quizNO);
$stmt->execute();

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Results Published</title>
    <link rel='stylesheet' href='style.css'>
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; }
        .success-box { background: white; padding: 40px 50px; border-radius: 12px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .success-box h2 { color: #27ae60; margin-bottom: 10px; }
        .success-box p { color: #555; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 12px 28px; background: linear-gradient(135deg, #667eea, #000); color: white; border-radius: 8px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class='success-box'>
        <h2>✅ Results Published Successfully!</h2>
        <p>Quiz <strong>$quizNO</strong> results for course <strong>$courseID</strong> are now visible to students.</p>
        <a class='btn' href='instructor_dashboard.php'>Back to Dashboard</a>
    </div>
</body>
</html>";

$conn->close();
?>
