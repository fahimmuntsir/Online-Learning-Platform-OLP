<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "learning_platform");
$student_id = $_SESSION['userID'];
$assignment_id = intval($_POST['assignment_id'] ?? 0);

// Validate assignment exists and deadline not passed
$stmt = $conn->prepare("SELECT deadline FROM assignment WHERE assignment_id = ?");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Assignment not found']);
    exit();
}
$row = $res->fetch_assoc();
if (new DateTime() > new DateTime($row['deadline'])) {
    echo json_encode(['success' => false, 'message' => 'Deadline passed']);
    exit();
}

// Check already submitted
$chk = $conn->prepare("SELECT submission_id FROM assignment_submission WHERE assignment_id=? AND student_id=?");
$chk->bind_param("is", $assignment_id, $student_id);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Already submitted']);
    exit();
}

// Handle file upload
if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error']);
    exit();
}

$file = $_FILES['submission_file'];
$maxSize = 50 * 1024 * 1024; // 50MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large']);
    exit();
}

$allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit();
}

$uploadDir = 'assignment_uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = $assignment_id . '_' . $student_id . '_' . time() . '.' . $ext;
$filePath = $uploadDir . $fileName;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit();
}

// Save to DB
$ins = $conn->prepare("INSERT INTO assignment_submission (assignment_id, student_id, file_path, submitted_at) VALUES (?, ?, ?, NOW())");
$ins->bind_param("iss", $assignment_id, $student_id, $filePath);
if ($ins->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}
