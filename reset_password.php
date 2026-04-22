<?php
$token = $_GET['token'] ?? '';
$message = "";

$conn = new mysqli("localhost", "root", "", "learning_platform");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $message = "❌ Passwords do not match!";
    } else {
        $result = $conn->query("SELECT email FROM password_resets WHERE token = '$token' AND created_at > NOW() - INTERVAL 1 HOUR");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $email = $row['email'];
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$conn->query("UPDATE userinfo SET Password = '$hashed_password' WHERE Email = '$email'");
            $conn->query("DELETE FROM password_resets WHERE token = '$token'");
            $message = "✅ Password updated! <a href='login.html'>Login here</a>";
        } else {
            $message = "❌ Invalid or expired link!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 400px; margin: 100px auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        input[type=password] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 12px; background: linear-gradient(to right, #6a11cb, #2575fc); color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .message { margin-top: 15px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>Reset Password</h2>
    <form method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <label>New Password:</label>
        <input type="password" name="new_password" required placeholder="Enter new password">
        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required placeholder="Confirm new password">
        <button type="submit">Reset Password</button>
    </form>
    <div class="message"><?php echo $message; ?></div>
</div>
</body>
</html>