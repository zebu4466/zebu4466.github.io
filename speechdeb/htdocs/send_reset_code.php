<?php
// Database connection
$conn = new mysqli('sql109.infinityfree.com', 'if0_38847172', 'Zeboosta4466', 'if0_38847172_speechdeb_users');

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get email
$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    die("Email is required.");
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die("The email you entered is not associated with any account.");
}
$stmt->close();

// Generate 6-digit random code
$code = rand(100000, 999999);

// Save the code into database
$stmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_code_expires = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE email = ?");
$stmt->bind_param('ss', $code, $email);
$stmt->execute();
$stmt->close();

// Now send email
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.zoho.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'support@speechdeb.infy.uk';
    $mail->Password   = 'Zeboosta#4466';  // ⚡ if you meant no # (double-check)
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('support@speechdeb.infy.uk', 'The Speechdeb Team');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Speechdeb Verification Code: ' . $code . '';
    $mail->Body = '
        Hello,<br><br>
        You have received this email because a password reset request was recently initiated for a Speechdeb Editor account associated with this email address.<br><br>
        If you made this request, go back to the Speechdeb website and enter this code into the Verification Code field.<br>
        The code will expire in 10 minutes.<br><br>
        If you did not make this request, you can safely ignore this email. The Speechdeb Team will never ask you for this code.<br><br>
        <b>Verification code: ' . $code . '</b><br><br>
        Thank you,<br>
        The Speechdeb Team
    ';

    $mail->send();
    echo 'Reset code email has been sent!';
} catch (Exception $e) {
    error_log("Mailer Error: {$mail->ErrorInfo}");
    echo "Failed to send reset email. Please try again later.";
}

$conn->close();
?>