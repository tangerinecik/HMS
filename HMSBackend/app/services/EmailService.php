<?php

namespace App\Services;

use SendGrid;
use SendGrid\Mail\Mail;

class EmailService
{
    private string $fromEmail;
    private string $fromName;
    private ?SendGrid $sendGrid;
    
    public function __construct()
    {
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'noreply@hotelfortuna.com';
        $this->fromName = $_ENV['FROM_NAME'] ?? 'Hotel Fortuna';
        
        // Initialize SendGrid
        $apiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
        if (!empty($apiKey)) {
            $this->sendGrid = new SendGrid($apiKey);
        } else {
            $this->sendGrid = null;
            error_log('EmailService: No SendGrid API key found - emails will be logged only');
        }
    }    /**
     * Send email verification email
     */
    public function sendVerificationEmail(string $toEmail, string $firstName, string $verificationToken): bool
    {
        $verificationUrl = $this->getVerificationUrl($verificationToken);
        
        $subject = 'Verify Your Email Address - Hotel Fortuna';
        $textMessage = $this->getVerificationEmailText($firstName, $verificationUrl);
        $htmlMessage = $this->getVerificationEmailHtml($firstName, $verificationUrl);
        
        return $this->sendEmail($toEmail, $subject, $textMessage, $htmlMessage);
    }

    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation(string $toEmail, string $firstName, array $bookingData): bool
    {
        $subject = 'Booking Confirmation - Hotel Fortuna';
        $textMessage = $this->getBookingConfirmationText($firstName, $bookingData);
        $htmlMessage = $this->getBookingConfirmationHtml($firstName, $bookingData);
        
        return $this->sendEmail($toEmail, $subject, $textMessage, $htmlMessage);
    }

    /**
     * Send email using SendGrid or log for development
     */
    private function sendEmail(string $toEmail, string $subject, string $textMessage, string $htmlMessage = ''): bool
    {
        // If SendGrid is not configured, fall back to logging
        if ($this->sendGrid === null) {
            return $this->logEmail($toEmail, $subject, $textMessage);
        }
        
        try {
            $email = new Mail();
            $email->setFrom($this->fromEmail, $this->fromName);
            $email->setSubject($subject);
            $email->addTo($toEmail);
            $email->addContent("text/plain", $textMessage);
            
            if (!empty($htmlMessage)) {
                $email->addContent("text/html", $htmlMessage);
            }

            $response = $this->sendGrid->send($email);
            
            if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
                error_log("EmailService: Email sent successfully to $toEmail");
                return true;
            } else {
                error_log("EmailService: SendGrid error " . $response->statusCode() . " - " . $response->body());
                return $this->logEmail($toEmail, $subject, $textMessage); // Fallback to logging
            }
            
        } catch (\Exception $e) {
            error_log("EmailService: Failed to send email - " . $e->getMessage());
            return $this->logEmail($toEmail, $subject, $textMessage); // Fallback to logging
        }
    }

    /**
     * Log email for development/fallback
     */
    private function logEmail(string $toEmail, string $subject, string $message): bool
    {
        try {
            // Log to PHP error log
            error_log("EMAIL SENT:");
            error_log("To: $toEmail");
            error_log("Subject: $subject");
            error_log("Message: $message");
            
            // Also log to a file for easier viewing
            $logMessage = sprintf(
                "[%s] EMAIL TO: %s\nSUBJECT: %s\nMESSAGE:\n%s\n%s\n",
                date('Y-m-d H:i:s'),
                $toEmail,
                $subject,
                $message,
                str_repeat('-', 80)
            );
            
            $logFile = __DIR__ . '/../logs/emails.log';
            if (is_dir(dirname($logFile))) {
                file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
            }
            
            return true; // Always return true for development
            
        } catch (\Exception $e) {
            error_log("Email logging failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get verification URL
     */
    private function getVerificationUrl(string $token): string
    {
        $baseUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173';
        return "{$baseUrl}/verify-email?token={$token}";
    }

    /**
     * Email verification text template
     */
    private function getVerificationEmailText(string $firstName, string $verificationUrl): string
    {
        return "Hello {$firstName},

Welcome to Hotel Fortuna!

Thank you for registering with us. To complete your registration, please verify your email address by clicking the link below:

{$verificationUrl}

This verification link will expire in 24 hours.

If you didn't create an account with us, please ignore this email.

Best regards,
Hotel Fortuna Team";
    }

    /**
     * Booking confirmation text template
     */
    private function getBookingConfirmationText(string $firstName, array $bookingData): string
    {
        return "Hello {$firstName},

Your booking has been confirmed!

Booking Details:
- Booking Reference: {$bookingData['ref_code']}
- Check-in: {$bookingData['check_in']}
- Check-out: {$bookingData['check_out']}
- Guests: {$bookingData['guests']}
- Total Amount: €{$bookingData['total_amount']}

We look forward to welcoming you to Hotel Fortuna!

Best regards,
Hotel Fortuna Team";
    }

    /**
     * Email verification HTML template
     */
    private function getVerificationEmailHtml(string $firstName, string $verificationUrl): string
    {
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Verify Your Email - Hotel Fortuna</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f8f9fa; }
        .button { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { background-color: #e9ecef; padding: 15px; text-align: center; font-size: 14px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Welcome to Hotel Fortuna!</h1>
        </div>
        <div class='content'>
            <h2>Hello {$firstName},</h2>
            <p>Thank you for registering with us. To complete your registration, please verify your email address by clicking the button below:</p>
            <p><a href='{$verificationUrl}' class='button'>Verify Email Address</a></p>
            <p>Or copy and paste this link in your browser: {$verificationUrl}</p>
            <p><strong>This verification link will expire in 24 hours.</strong></p>
            <p>If you didn't create an account with us, please ignore this email.</p>
        </div>
        <div class='footer'>
            <p>Best regards,<br>Hotel Fortuna Team</p>
        </div>
    </div>
</body>
</html>";
    }

    /**
     * Booking confirmation HTML template
     */
    private function getBookingConfirmationHtml(string $firstName, array $bookingData): string
    {
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Booking Confirmation - Hotel Fortuna</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f8f9fa; }
        .booking-details { background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { background-color: #e9ecef; padding: 15px; text-align: center; font-size: 14px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>Booking Confirmed!</h1>
        </div>
        <div class='content'>
            <h2>Hello {$firstName},</h2>
            <p>Your booking has been confirmed! We're excited to welcome you to Hotel Fortuna.</p>
            <div class='booking-details'>
                <h3>Booking Details:</h3>
                <p><strong>Booking Reference:</strong> {$bookingData['ref_code']}</p>
                <p><strong>Check-in:</strong> {$bookingData['check_in']}</p>
                <p><strong>Check-out:</strong> {$bookingData['check_out']}</p>
                <p><strong>Guests:</strong> {$bookingData['guests']}</p>
                <p><strong>Total Amount:</strong> €{$bookingData['total_amount']}</p>
            </div>
            <p>We look forward to welcoming you to Hotel Fortuna!</p>
        </div>
        <div class='footer'>
            <p>Best regards,<br>Hotel Fortuna Team</p>
        </div>
    </div>
</body>
</html>";
    }
}
