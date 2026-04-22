<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    $conn = new mysqli("localhost", "root", "", "learning_platform");

    $stmt = $conn->prepare("SELECT UserID FROM userinfo WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $conn->query("DELETE FROM password_resets WHERE email = '$email'");
        $conn->query("INSERT INTO password_resets (email, token) VALUES ('$email', '$token')");

        $resetLink = "http://localhost/Online-Learning-Platform-OLP-/reset_password.php?token=$token";

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'muntasir.99fahim@gmail.com';
        $mail->Password = 'dkfrzhnkppezggzh';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('muntasir.99fahim@gmail.com', 'Online Learning Platform');
        $mail->addAddress($email);
        $mail->Subject = 'Password Reset Request';
        $mail->Body = "Click this link to reset your password:\n\n$resetLink\n\nThis link expires in 1 hour.";

        $mail->send();
        $message = "✅ Password reset link sent to your email!";
    } else {
        $message = "❌ No account found with this email.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 400px; margin: 100px auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        input[type=email] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 12px; background: linear-gradient(to right, #6a11cb, #2575fc); color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .message { margin-top: 15px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>Forgot Password</h2>
    <p>তোমার Email দাও — Password reset link পাঠানো হবে।</p>
    <form method="POST">
        <label>Email Address:</label>
        <input type="email" name="email" required placeholder="Enter your email">
        <button type="submit">Send Reset Link</button>
    </form>
    <div class="message"><?php echo $message; ?></div>
    <br>
    <a href="login.html">← Back to Login</a>
</div>
</body>
</html>