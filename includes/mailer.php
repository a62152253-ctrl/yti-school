<?php

class MailerService {
    private static $instance = null;

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function sendPaymentNotification($transaction) {
        $subject = "Nowa płatność: " . $transaction['student_name'] . " - " . number_format($transaction['amount'], 2, ',', ' ') . " PLN";
        
        $html = "<html><body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>";
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
        $html .= "<td style='padding: 10px; font-family: monospace;'>" . htmlspecialchars($transaction['stripe_id']) . "</td>";
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
        
        $html .= "</body></html>";

        return $this->sendMail(
            ADMIN_EMAIL,
            $subject,
            $html
        );
    }

    private function sendMail($to, $subject, $htmlContent) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_ADDRESS . ">\r\n";
        $headers .= "Reply-To: " . MAIL_FROM_ADDRESS . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        return mail($to, $subject, $htmlContent, $headers);
    }
}
