<?php
session_start();
$conn = new mysqli("localhost", "root", "", "learning_platform");

if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$userID = $_SESSION['userID'] ?? '';

if (empty($userID)) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT Course_ID FROM course WHERE UserID = ?");
$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row['Course_ID'];
}

echo json_encode($courses);
$conn->close();
?>
