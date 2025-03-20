<?php
require_once 'config/config.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Display environment variables (sanitized for security)
echo "<h2>Environment Variables Check:</h2>";
echo "SMTP_HOST: " . (getenv('SMTP_HOST') ? "Set (value hidden)" : "Not set") . "<br>";
echo "SMTP_USERNAME: " . (getenv('SMTP_USERNAME') ? "Set (value hidden)" : "Not set") . "<br>";
echo "SMTP_PASSWORD: " . (getenv('SMTP_PASSWORD') ? "Set (value hidden)" : "Not set") . "<br>";
echo "SMTP_PORT: " . getenv('SMTP_PORT') . "<br>";
echo "SMTP_FROM_EMAIL: " . (getenv('SMTP_FROM_EMAIL') ? "Set (value hidden)" : "Not set") . "<br>";
echo "SMTP_FROM_NAME: " . getenv('SMTP_FROM_NAME') . "<br>";
echo "APP_URL: " . getenv('APP_URL') . "<br>";

echo "<h2>Testing SMTP Connection:</h2>";

function testSMTP() {
    $mail = new PHPMailer(true);
    try {
        // Enable debugging
        $mail->SMTPDebug = 3; // Output more verbose debug info
        $mail->Debugoutput = 'html';
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT');
        $mail->setFrom(getenv('SMTP_FROM_EMAIL'), getenv('SMTP_FROM_NAME'));
        
        // Try connecting to the server without sending email
        $mail->smtpConnect();
        
        echo "<div style='color:green'>SMTP connection successful!</div>";
        return true;
    } catch (Exception $e) {
        echo "<div style='color:red'>SMTP Error: " . $mail->ErrorInfo . "</div>";
        echo "<div style='color:red'>Exception: " . $e->getMessage() . "</div>";
        return false;
    }
}

if (testSMTP()) {
    // Try sending a test email
    echo "<h2>Attempting to send test email:</h2>";
    
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('SMTP_PORT');
        $mail->setFrom(getenv('SMTP_FROM_EMAIL'), getenv('SMTP_FROM_NAME'));
        
        // Set recipient - change this to your email
        $mail->addAddress(getenv('SMTP_USERNAME')); // Using your own email as test recipient
        
        $mail->isHTML(true);
        $mail->Subject = 'MatSFX Test Email';
        $mail->Body = '<h2>Test Email from MatSFX</h2><p>This is a test email to verify SMTP configuration.</p>';
        $mail->AltBody = 'Test Email from MatSFX - This is a test email to verify SMTP configuration.';
        
        $mail->send();
        echo "<div style='color:green'>Test email sent successfully!</div>";
    } catch (Exception $e) {
        echo "<div style='color:red'>Failed to send test email: " . $mail->ErrorInfo . "</div>";
    }
}
