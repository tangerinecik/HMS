<?php

namespace App\Services;

class EmailService
{
    private string $fromEmail;
    private string $fromName;
    
    public function __construct()
    {
        $this->fromEmail = $_ENV['FROM_EMAIL'] ?? 'noreply@hotelfortuna.com';
        $this->fromName = $_ENV['FROM_NAME'] ?? 'Hotel Fortuna';
        
        error_log('EmailService: Initialized for development (logging emails)');
    }

    /**
     * Send email verification email
     */
    public function sendVerificationEmail(string $toEmail, string $firstName, string $verificationToken): bool
    {
        $verificationUrl = $this->getVerificationUrl($verificationToken);
        
        $subject = 'Verify Your Email Address - Hotel Fortuna';
        $message = $this->getVerificationEmailText($firstName, $verificationUrl);
        
        return $this->sendEmail($toEmail, $subject, $message);
    }

    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation(string $toEmail, string $firstName, array $bookingData): bool
    {
        $subject = 'Booking Confirmation - Hotel Fortuna';
        $message = $this->getBookingConfirmationText($firstName, $bookingData);
        
        return $this->sendEmail($toEmail, $subject, $message);
    }

    /**
     * Send email (development version - logs to file and error log)
     */
    private function sendEmail(string $toEmail, string $subject, string $message): bool
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
}
