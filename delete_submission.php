<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: login.html");
    exit();
}
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "learning_platform");
$user_id = $_SESSION['userID'];
$submission_id = intval($_POST['submission_id'] ?? 0);

$stmt = $conn->prepare("SELECT s.file_path, a.deadline 
                        FROM assignment_submission s 
                        JOIN assignment a ON s.assignment_id = a.assignment_id 
                        WHERE s.submission_id = ? AND s.student_id = ?");
$stmt->bind_param("is", $submission_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit();
}

$row = $res->fetch_assoc();
$deadline = new DateTime($row['deadline']);
$now = new DateTime();

if ($now > $deadline) {
    echo json_encode(['success' => false, 'message' => 'Deadline passed']);
    exit();
}

$filePath = $row['file_path'];
if (file_exists($filePath)) {
    unlink($filePath);
}

$del = $conn->prepare("DELETE FROM assignment_submission WHERE submission_id = ? AND student_id = ?");
$del->bind_param("is", $submission_id, $user_id);
if ($del->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}
?>