<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Instructor', 'Admin'])) {
    header("Location: login.html");
    exit();
}
$conn = new mysqli("localhost", "root", "", "learning_platform");
$submission_id = intval($_GET['id'] ?? 0);
$instructor_id = $_SESSION['userID'];

if ($_SESSION['role'] === 'Instructor') {
    $stmt = $conn->prepare("SELECT s.file_path FROM assignment_submission s JOIN assignment a ON s.assignment_id = a.assignment_id WHERE s.submission_id = ? AND a.instructor_id = ?");
    $stmt->bind_param("is", $submission_id, $instructor_id);
} else {
    $stmt = $conn->prepare("SELECT file_path FROM assignment_submission WHERE submission_id = ?");
    $stmt->bind_param("i", $submission_id);
}
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) { die("Not found or access denied."); }
$row = $res->fetch_assoc();
$filePath = $row['file_path'];
if (!file_exists($filePath)) { die("File not found on server."); }
$ext = pathinfo($filePath, PATHINFO_EXTENSION);
$mimes = ['pdf'=>'application/pdf','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$mime = $mimes[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="submission_' . $submission_id . '.' . $ext . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit();
