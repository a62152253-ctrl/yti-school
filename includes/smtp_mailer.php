<?php

class SmtpMailer {
    private static $instance = null;
    private $host;
    private $port;
    private $user;
    private $pass;

    private function __construct() {
        $this->host = SMTP_HOST;
        $this->port = SMTP_PORT;
        $this->user = SMTP_USER;
        $this->pass = SMTP_PASS;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Send password reset email via Gmail SMTP
     */
    public function sendPasswordResetEmail($to, $resetLink, $expiresIn = 3600) {
        $subject = "Reset hasła - YTI School Hub";
        $expiresMinutes = ceil($expiresIn / 60);
        
        $html = "<html><body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>";
        $html .= "<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>";
        
        $html .= "<div style='text-align: center; margin-bottom: 30px;'>";
        $html .= "<h2 style='color: #ff0000; margin: 0;'>🔐 Reset Hasła</h2>";
        $html .= "<p style='color: #666; margin: 5px 0 0 0;'>YTI School Hub</p>";
        $html .= "</div>";
        
        $html .= "<div style='background: #f5f5f5; border-left: 4px solid #ff0000; padding: 20px; margin-bottom: 20px;'>";
        $html .= "<p>Otrzymaliśmy prośbę o reset hasła do Twojego konta.</p>";
        $html .= "<p><strong>Link resetujący:</strong></p>";
        $html .= "<div style='text-align: center; margin: 20px 0;'>";
        $html .= "<a href='" . htmlspecialchars($resetLink) . "' style='display: inline-block; background: #ff0000; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Zresetuj Hasło</a>";
        $html .= "</div>";
        $html .= "<p style='color: #999; font-size: 0.9em;'>Lub skopiuj link:</p>";
        $html .= "<p style='background: white; padding: 10px; border-radius: 3px; overflow-wrap: break-word; word-break: break-all; font-family: monospace; font-size: 0.85em;'>" . htmlspecialchars($resetLink) . "</p>";
        $html .= "</div>";
        
        $html .= "<div style='background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        $html .= "<p style='margin: 0;'>";
        $html .= "<strong>⏰ Ważność:</strong> Ten link wygaśnie za <strong>" . $expiresMinutes . " minut</strong>.<br>";
        $html .= "Link będzie ważny do: <strong>" . date('Y-m-d H:i', time() + $expiresIn) . " (czas serwera)</strong>";
        $html .= "</p>";
        $html .= "</div>";
        
        $html .= "<div style='background: #e8f4f8; border: 1px solid #0066cc; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
        $html .= "<p style='margin: 0; color: #0066cc;'>";
        $html .= "<strong>ℹ️ Bezpieczeństwo:</strong><br>";
        $html .= "Jeśli nie prosiłeś o reset hasła, zignoruj tę wiadomość. Twoje konto jest bezpieczne.<br>";
        $html .= "Nigdy nie udostępniaj tego linka innym osobom!";
        $html .= "</p>";
        $html .= "</div>";
        
        $html .= "<hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>";
        
        $html .= "<footer style='text-align: center; color: #999; font-size: 0.85em;'>";
        $html .= "<p style='margin: 0;'>© " . date('Y') . " YTI School Hub. Wiadomość automatyczna.</p>";
        $html .= "<p style='margin: 5px 0 0 0;'><a href='https://yti-school.pl' style='color: #0066cc; text-decoration: none;'>https://yti-school.pl</a></p>";
        $html .= "</footer>";
        
        $html .= "</div></body></html>";

        return $this->sendViaGmail($to, $subject, $html);
    }

    /**
     * Send payment notification email
     */
    public function sendPaymentNotification($to, $transaction) {
        $subject = "Nowa płatność: " . $transaction['student_name'] . " - " . number_format($transaction['amount'], 2, ',', ' ') . " PLN";
        
        $html = "<html><body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>";
        $html .= "<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>";
        $html .= "<h2 style='color: #ff0000; border-bottom: 2px solid #ff0000; padding-bottom: 10px;'>🎓 Nowa Płatność - YTI School Hub</h2>";
        
        $html .= "<table style='width: 100%; margin-top: 20px; border-collapse: collapse;'>";
        $html .= "<tr style='background: #f5f5f5;'>";
        $html .= "<td style='padding: 10px; font-weight: bold; width: 150px;'>Uczeń:</td>";
        $html .= "<td style='padding: 10px;'>" . htmlspecialchars($transaction['student_name']) . " (" . htmlspecialchars($transaction['student_email']) . ")</td>";
        $html .= "</tr>";
        
        $html .= "<tr>";
        $html .= "<td style='padding: 10px; font-weight: bold;'>Materiał:</td>";
        $html .= "<td style='padding: 10px;'>" . htmlspecialchars($transaction['note_title']) . "</td>";
        $html .= "</tr>";
        
        $html .= "<tr style='background: #f5f5f5;'>";
        $html .= "<td style='padding: 10px; font-weight: bold;'>Nauczyciel:</td>";
        $html .= "<td style='padding: 10px;'>" . htmlspecialchars($transaction['teacher_name']) . "</td>";
        $html .= "</tr>";
        
        $html .= "<tr>";
        $html .= "<td style='padding: 10px; font-weight: bold;'>Kwota:</td>";
        $html .= "<td style='padding: 10px; font-size: 1.2em; color: #ff0000; font-weight: bold;'>" . number_format($transaction['amount'], 2, ',', ' ') . " PLN</td>";
        $html .= "</tr>";
        
        $html .= "<tr style='background: #f5f5f5;'>";
        $html .= "<td style='padding: 10px; font-weight: bold;'>Stripe ID:</td>";
        $html .= "<td style='padding: 10px; font-family: monospace; font-size: 0.9em;'>" . htmlspecialchars($transaction['stripe_id']) . "</td>";
        $html .= "</tr>";
        
        $html .= "<tr>";
        $html .= "<td style='padding: 10px; font-weight: bold;'>Data:</td>";
        $html .= "<td style='padding: 10px;'>" . date('Y-m-d H:i:s', strtotime($transaction['created_at'])) . "</td>";
        $html .= "</tr>";
        $html .= "</table>";
        
        $html .= "<div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 0.9em;'>";
        $html .= "<p>Status: <strong style='color: #28a745;'>✓ OPŁACONE</strong></p>";
        $html .= "<p>Logowanie do panelu: <a href='https://yti-school.pl/dashboard.php' style='color: #ff0000;'>https://yti-school.pl/dashboard.php</a></p>";
        $html .= "<p style='margin-top: 15px; color: #999; font-size: 0.8em;'>Wiadomość automatyczna z YTI School Hub. Nie odpowiadaj na tego maila.</p>";
        $html .= "</div>";
        
        $html .= "</div></body></html>";

        return $this->sendViaGmail($to, $subject, $html);
    }

    private function sendViaGmail($to, $subject, $htmlContent) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">\r\n";
        $headers .= "Reply-To: " . MAIL_FROM_ADDRESS . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        // Encode subject in UTF-8
        $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        // Try native mail() first (works if sendmail_path is configured)
        if (function_exists('mail')) {
            return mail($to, $subject, $htmlContent, $headers);
        }

        return false;
    }
}
