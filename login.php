<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "learning_platform";
$logFile = 'debug_log.txt';
$logData = "";
$conn = mysqli_connect($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo("Connection failed: " . $conn->connect_error);
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = trim($_POST['UserID']);
    $password = trim($_POST['Password']);
    $query = "SELECT UserID, Password, Role FROM Userinfo WHERE UserID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $logData .= "Database Password: " . $row['Password'] . "\n";
        $logData .= "User Role: " . $password . "\n";
        file_put_contents($logFile, $logData, FILE_APPEND);
        if (password_verify($password, $row['Password'])) {
            $_SESSION['userID'] = $row['UserID'];
            $_SESSION['role'] = $row['Role'];
            if ($row['Role'] === 'Student') {
                header("Location: student_dashboard.php");
                exit();
            } elseif ($row['Role'] === 'Instructor') {
                header("Location: instructor_dashboard.php");
                exit();
            } elseif ($row['Role'] === 'Admin') {
                header("Location: admin_dashboard.php");
                exit();
            } else {
                echo "Error: Invalid role. Please contact support.";
            }
        } else {
            echo "<script>alert('Invalid Password'); window.location.href='login.html';</script>";
        }
    } else {
        echo "<script>alert('Invalid User ID'); window.location.href='login.html';</script>";
    }
    $stmt->close();
}
$conn->close();
?>