<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header("Location: login.html");
    exit();
}
$conn = new mysqli("localhost", "root", "", "learning_platform");
$instructor_id = $_SESSION['userID'];

$course_id        = $_POST['Course_ID'];
$assignment_number = intval($_POST['assignment_number']);
$description      = $_POST['description'] ?? '';
$deadline_date    = $_POST['deadline_date'];
$deadline_time    = $_POST['deadline_time'];
$deadline         = $deadline_date . ' ' . $deadline_time . ':00';

// Handle question file upload
$question_file_path = null;

if (isset($_FILES['question_file']) && $_FILES['question_file']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $allowed_exts = ['pdf', 'doc', 'docx'];

    $file_tmp  = $_FILES['question_file']['tmp_name'];
    $file_name = $_FILES['question_file']['name'];
    $file_size = $_FILES['question_file']['size'];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $max_size = 20 * 1024 * 1024; // 20 MB

    if (!in_array($file_ext, $allowed_exts)) {
        header("Location: create_assignment.php?error=invalid_type");
        exit();
    }
    if ($file_size > $max_size) {
        header("Location: create_assignment.php?error=too_large");
        exit();
    }

    // Create upload folder if not exists
    $upload_dir = 'assignment_questions/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename: courseID_assignNum_timestamp.ext
    $safe_name = $course_id . '_assign' . $assignment_number . '_' . time() . '.' . $file_ext;
    $dest_path = $upload_dir . $safe_name;

    if (move_uploaded_file($file_tmp, $dest_path)) {
        $question_file_path = $dest_path;
    } else {
        header("Location: create_assignment.php?error=upload_failed");
        exit();
    }
}

// Insert into DB — includes question_file column
$stmt = $conn->prepare(
    "INSERT INTO assignment (Course_ID, instructor_id, assignment_number, description, deadline, question_file)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("ssisss", $course_id, $instructor_id, $assignment_number, $description, $deadline, $question_file_path);

if ($stmt->execute()) {
    header("Location: create_assignment.php?success=1");
} else {
    header("Location: create_assignment.php?error=1");
}
exit();