<?php
// ===== INCLUDE CONFIG =====
require_once __DIR__ . '/../config/email_config.php';

// ===== LOAD PHPMailer =====
require_once __DIR__ . '/../vendor/autoload.php'; // Agar Composer se install kiya hai

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailHandler {
    private $smtp_host;
    private $smtp_port;
    private $username;
    private $password;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->username = SMTP_USERNAME;
        $this->password = SMTP_PASSWORD;
        $this->from_email = SMTP_FROM_EMAIL;
        $this->from_name = SMTP_FROM_NAME;
    }
    
    // ===== SEND EMAIL USING PHPMailer =====
    private function sendEmail($to, $subject, $message, $is_html = true) {
        // Check if email is enabled
        if (!EMAIL_ENABLED) {
            error_log("Email sending is disabled");
            return false;
        }
        
        // Debug mode
        if (EMAIL_DEBUG) {
            error_log("Sending email to: " . $to);
            error_log("Subject: " . $subject);
        }
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // ===== SERVER SETTINGS =====
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->username;
            $mail->Password = $this->password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            
            // ===== DEBUG SETTINGS (Remove in production) =====
            if (EMAIL_DEBUG) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer Debug: $str");
                };
            }
            
            // ===== SENDER & RECIPIENT =====
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);
            
            // ===== CONTENT =====
            $mail->isHTML($is_html);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            if (!$is_html) {
                $mail->AltBody = strip_tags($message);
            }
            
            // ===== SEND =====
            $mail->send();
            error_log("Email sent successfully to: " . $to);
            return true;
            
        } catch (Exception $e) {
            error_log("Email failed to send to: " . $to);
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    // ===== SEND USER CONFIRMATION EMAIL =====
    public function sendUserConfirmation($user_email, $user_name, $job_title, $company_name) {
        $subject = "Application Submitted Successfully - " . $job_title;
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; background: #f9f9f9; border-radius: 0 0 5px 5px; }
                .footer { background: #333; color: white; padding: 10px; text-align: center; font-size: 12px; margin-top: 20px; border-radius: 5px; }
                .highlight { color: #4CAF50; font-weight: bold; }
                .info-box { background: white; padding: 15px; border-left: 4px solid #4CAF50; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>✓ Application Confirmed</h2>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
                    
                    <p>Thank you for submitting your application for the position of <strong>" . htmlspecialchars($job_title) . "</strong> at <strong>" . htmlspecialchars($company_name) . "</strong>.</p>
                    
                    <div class='info-box'>
                        <p><strong>Application Details:</strong></p>
                        <p>• Position: " . htmlspecialchars($job_title) . "</p>
                        <p>• Company: " . htmlspecialchars($company_name) . "</p>
                        <p>• Date: " . date('F d, Y') . "</p>
                    </div>
                    
                    <p><strong>What happens next?</strong></p>
                    <ul>
                        <li>Our HR team will review your application</li>
                        <li>If shortlisted, you will receive an interview invitation</li>
                        <li>The process typically takes 5-7 working days</li>
                    </ul>
                    
                    <p>If you have any questions, please don't hesitate to contact us.</p>
                    
                    <p>Best regards,<br>
                    <span class='highlight'>" . htmlspecialchars($company_name) . " Team</span></p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . htmlspecialchars($company_name) . ". All rights reserved.</p>
                    <p style='font-size: 10px;'>This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($user_email, $subject, $message, true);
    }
    
    // ===== SEND ADMIN NOTIFICATION EMAIL =====
    public function sendAdminNotification($job_title, $applicant_name, $applicant_email, $applicant_phone, $job_id, $application_id) {
        $subject = "New Job Application Received - " . $job_title;
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2196F3; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; background: #f9f9f9; border-radius: 0 0 5px 5px; }
                .footer { background: #333; color: white; padding: 10px; text-align: center; font-size: 12px; margin-top: 20px; border-radius: 5px; }
                .info-box { background: white; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0; }
                .label { font-weight: bold; color: #555; }
                .alert { background: #FFF3CD; padding: 10px; border-left: 4px solid #FFC107; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔔 New Job Application Alert</h2>
                </div>
                <div class='content'>
                    <p>A new application has been submitted for the position of <strong>" . htmlspecialchars($job_title) . "</strong>.</p>
                    
                    <div class='info-box'>
                        <h3>Applicant Details:</h3>
                        <p><span class='label'>Name:</span> " . htmlspecialchars($applicant_name) . "</p>
                        <p><span class='label'>Email:</span> " . htmlspecialchars($applicant_email) . "</p>
                        <p><span class='label'>Phone:</span> " . htmlspecialchars($applicant_phone) . "</p>
                        <p><span class='label'>Application ID:</span> #" . $application_id . "</p>
                        <p><span class='label'>Job ID:</span> #" . $job_id . "</p>
                        <p><span class='label'>Applied Date:</span> " . date('F d, Y H:i:s') . "</p>
                    </div>
                    
                    <div class='alert'>
                        <p><strong>Action Required:</strong></p>
                        <ul>
                            <li>Review the application in the admin panel</li>
                            <li>Verify the applicant's qualifications</li>
                            <li>Schedule an interview if suitable</li>
                        </ul>
                    </div>
                    
                    <p>You can view all applications in the <a href='admin/applications.php'>Admin Dashboard</a>.</p>
                    
                    <p style='font-size: 12px; color: #888;'>This is an automated notification. Please do not reply to this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Job Portal System. Automated Notification</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail(ADMIN_EMAIL, $subject, $message, true);
    }
}
?>