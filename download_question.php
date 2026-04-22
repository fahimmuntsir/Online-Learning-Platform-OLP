<?php
// download_question.php
// Allows enrolled students (and instructors) to securely download assignment question files.

session_start();
if (!isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "learning_platform");
$user_id = $_SESSION['userID'];
$role    = $_SESSION['role'] ?? '';

$assignment_id = intval($_GET['assignment_id'] ?? 0);
if ($assignment_id <= 0) {
    http_response_code(400);
    die("Invalid request.");
}

// Fetch assignment + question file
$stmt = $conn->prepare("SELECT a.question_file, a.Course_ID, a.assignment_number FROM assignment a WHERE a.assignment_id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || empty($row['question_file'])) {
    http_response_code(404);
    die("No question file found for this assignment.");
}

$file_path = $row['question_file'];
$course_id = $row['Course_ID'];

// Authorization check
if ($role === 'Student') {
    // Must be enrolled in this course
    $s = $conn->prepare("SELECT StudentID FROM student WHERE UserID = ?");
    $s->bind_param("s", $user_id);
    $s->execute();
    $srow = $s->get_result()->fetch_assoc();
    $student_id = $srow['StudentID'] ?? $user_id;

    $enroll = $conn->prepare("SELECT * FROM enrollment WHERE course_ID = ? AND StudentID = ?");
    $enroll->bind_param("ss", $course_id, $student_id);
    $enroll->execute();
    if ($enroll->get_result()->num_rows === 0) {
        http_response_code(403);
        die("Access denied. You are not enrolled in this course.");
    }
} elseif ($role === 'Instructor') {
    // Must be the instructor of this course
    $chk = $conn->prepare("SELECT * FROM assignment WHERE assignment_id = ? AND instructor_id = ?");
    $chk->bind_param("is", $assignment_id, $user_id);
    $chk->execute();
    if ($chk->get_result()->num_rows === 0) {
        http_response_code(403);
        die("Access denied.");
    }
} else {
    http_response_code(403);
    die("Access denied.");
}

// Serve the file
if (!file_exists($file_path)) {
    http_response_code(404);
    die("File not found on server.");
}

$ext      = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$mime_map = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
$mime = $mime_map[$ext] ?? 'application/octet-stream';

$download_name = 'Assignment_' . $row['assignment_number'] . '_' . $course_id . '.' . $ext;

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, no-store, must-revalidate');
readfile($file_path);
exit();