<?php
session_start();
$conn = new mysqli("localhost", "root", "", "learning_platform");

if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$courseID = $_GET['Course_ID'] ?? '';

if (empty($courseID)) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT Quiz_NO FROM quiz_description WHERE Course_ID = ?");
$stmt->bind_param("s", $courseID);
$stmt->execute();
$result = $stmt->get_result();

$quizzes = [];
while ($row = $result->fetch_assoc()) {
    $quizzes[] = $row['Quiz_NO'];
}

echo json_encode($quizzes);
$conn->close();
?>
