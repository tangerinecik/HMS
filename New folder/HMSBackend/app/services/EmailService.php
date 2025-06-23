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
            error_log('EmailService: No SendGrid API key found');
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
     * Send password change confirmation email
     */
    public function sendPasswordChangeConfirmation(string $toEmail, string $firstName): bool
    {
        $subject = 'Password Changed - Hotel Fortuna';
        $textMessage = $this->getPasswordChangeEmailText($firstName);
        $htmlMessage = $this->getPasswordChangeEmailHtml($firstName);

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
     * Send email using SendGrid
     */
    private function sendEmail(string $toEmail, string $subject, string $textMessage, string $htmlMessage = ''): bool
    {
        if ($this->sendGrid === null) {
            error_log("EmailService: Cannot send email - SendGrid not configured");
            return false;
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
                return false;
            }
            
        } catch (\Exception $e) {
            error_log("EmailService: Failed to send email - " . $e->getMessage());
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
     * Email verification HTML template
     */
    private function getVerificationEmailHtml(string $firstName, string $verificationUrl): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Verify Your Email - Hotel Fortuna</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 12px 30px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Hotel Fortuna</h1>
                </div>
                <div class='content'>
                    <h2>Welcome, {$firstName}!</h2>
                    <p>Thank you for registering with Hotel Fortuna. To complete your registration, please verify your email address by clicking the button below:</p>
                    <p style='text-align: center;'>
                        <a href='{$verificationUrl}' class='button'>Verify Email Address</a>
                    </p>
                    <p><strong>Note:</strong> This verification link will expire in 24 hours.</p>
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
     * Booking confirmation HTML template
     */
    private function getBookingConfirmationHtml(string $firstName, array $bookingData): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Booking Confirmation - Hotel Fortuna</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #27ae60; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .booking-details { background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Hotel Fortuna</h1>
                    <h2>Booking Confirmed!</h2>
                </div>
                <div class='content'>
                    <h2>Hello, {$firstName}!</h2>
                    <p>Your booking has been confirmed. Here are your booking details:</p>
                    <div class='booking-details'>
                        <div class='detail-row'>
                            <strong>Booking Reference:</strong>
                            <span>{$bookingData['ref_code']}</span>
                        </div>
                        <div class='detail-row'>
                            <strong>Check-in:</strong>
                            <span>{$bookingData['check_in']}</span>
                        </div>
                        <div class='detail-row'>
                            <strong>Check-out:</strong>
                            <span>{$bookingData['check_out']}</span>
                        </div>
                        <div class='detail-row'>
                            <strong>Guests:</strong>
                            <span>{$bookingData['guests']}</span>
                        </div>
                        <div class='detail-row'>
                            <strong>Total Amount:</strong>
                            <span><strong>€{$bookingData['total_amount']}</strong></span>
                        </div>
                    </div>
                    <p>We look forward to welcoming you to Hotel Fortuna!</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>Hotel Fortuna Team</p>
                </div>
            </div>
        </body>
        </html>";
    }    /**
     * Password change email text template
     */
    private function getPasswordChangeEmailText(string $firstName): string
    {
        return "Hello {$firstName},

Your password has been successfully changed for your Hotel Fortuna account.

If you did not make this change, please contact us immediately at support@hotelfortuna.com.

For security reasons:
- We recommend using a strong, unique password
- Never share your password with anyone
- Log out of shared devices after use

Best regards,
Hotel Fortuna Security Team";
    }

    /**
     * Password change email HTML template
     */
    private function getPasswordChangeEmailHtml(string $firstName): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Password Changed - Hotel Fortuna</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #e74c3c; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .security-tips { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .alert { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔒 Password Changed</h1>
                </div>
                <div class='content'>
                    <h2>Hello, {$firstName}!</h2>
                    <p>Your password has been successfully changed for your Hotel Fortuna account.</p>
                    
                    <div class='alert'>
                        <strong>Important:</strong> If you did not make this change, please contact us immediately at <strong>support@hotelfortuna.com</strong>.
                    </div>
                    
                    <div class='security-tips'>
                        <h3>🛡️ Security Recommendations:</h3>
                        <ul>
                            <li>Use a strong, unique password</li>
                            <li>Never share your password with anyone</li>
                            <li>Log out of shared devices after use</li>
                            <li>Enable two-factor authentication if available</li>
                        </ul>
                    </div>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>Hotel Fortuna Security Team</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Test email functionality
     */
    public function testEmail(string $toEmail): bool
    {
        $subject = 'Test Email - Hotel Fortuna';
        $textMessage = 'This is a test email from Hotel Fortuna to confirm email functionality is working correctly.';
        $htmlMessage = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Test Email</title>
        </head>
        <body>
            <h2>Test Email - Hotel Fortuna</h2>
            <p>This is a test email from Hotel Fortuna to confirm email functionality is working correctly.</p>
            <p>If you received this email, the email service is functioning properly.</p>
        </body>
        </html>';
        
        return $this->sendEmail($toEmail, $subject, $textMessage, $htmlMessage);
    }

    /**
     * Check if email service is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->sendGrid !== null;
    }

    /**
     * Get email service status information
     */
    public function getStatus(): array
    {
        $apiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
        
        return [
            'service' => 'EmailService',
            'provider' => 'SendGrid', 
            'configured' => $this->isConfigured(),
            'api_key_set' => !empty($apiKey),
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName
        ];
    }
}
