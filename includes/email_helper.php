<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

function sendEmailNotification($toEmail, $toName, $subject, $body) {
    // --- LOAD SECURE CONFIGURATION ---
    $config = require 'mailer_config.php';
    
    $mail = new PHPMailer(true);

    try {
        // --- 1. SERVER SETTINGS ---
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'];
        $mail->Password   = $config['smtp_pass']; // Securely loaded
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- RECIPIENTS ---
        $mail->setFrom($config['from_email'], $config['from_name']); 
        $mail->addAddress($toEmail, $toName);     

        // --- 3. EMBED LOGO IMAGE ---
      $logoPath = 'assets/images/bclogo.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'my_logo');
        }

        // --- 4. MODERN SKY BLUE UI TEMPLATE ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        $email_template = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; }
                .email-container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
                .header-bar { height: 10px; background: linear-gradient(90deg, #023e8a 0%, #0096c7 100%); }
                .logo-section { text-align: center; padding: 35px 20px 15px 20px; background-color: #ffffff; }
                
                /* --- UPDATED LOGO SIZE HERE (280px) --- */
                .logo-img { max-width: 280px; height: auto; }
                
                .content-section { padding: 20px 40px 40px 40px; color: #333333; line-height: 1.7; font-size: 16px; }
                .highlight-text { color: #0077b6; font-weight: 700; }
                .footer-section { background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #eeeeee; }
                .footer-text { font-size: 12px; color: #888888; margin: 0; }
                .btn-link { display: inline-block; margin-top: 20px; padding: 12px 25px; background-color: #0077b6; color: #ffffff !important; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header-bar'></div>

                <div class='logo-section'>
                    <img src='cid:my_logo' alt='Company Logo' class='logo-img'>
                </div>

                <div class='content-section'>
                    <h2 style='color: #023e8a; margin-top: 0; font-size: 22px; border-bottom: 2px solid #eef2f5; padding-bottom: 15px;'>
                        Notification Update
                    </h2>
                    
                    <p>$body</p>

                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='https://helpdesk.bansalancoop.com/' class='btn-link'>Login to Portal</a>
                    </div>
                </div>

                <div class='footer-section'>
                    <p class='footer-text'>&copy; " . date("Y") . "BC Ticketing  Services. Lejap.</p>
                    <p class='footer-text' style='margin-top: 5px;'>This is an automated message. Please do not reply directly to this email.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->Body = $email_template;
        $mail->AltBody = strip_tags(str_replace("<br>", "\n", $body)); 

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>