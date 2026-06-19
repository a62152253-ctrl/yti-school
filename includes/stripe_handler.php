<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/mailer.php';

use Stripe\Stripe;
use Stripe\StripeClient;

class StripePaymentHandler {
    private $stripeClient;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        Stripe::setApiKey(STRIPE_SECRET_KEY);
        $this->stripeClient = new StripeClient(['api_key' => STRIPE_SECRET_KEY]);
    }

    /**
     * Create a Stripe checkout session for a premium note
     */
    public function createCheckoutSession($note_id, $user_id, $returnUrl) {
        try {
            // Fetch note and user details
            $stmt = $this->pdo->prepare("
                SELECT n.id, n.title, n.premium_price, n.subject, 
                       u.id as teacher_id, u.username as teacher_name,
                       cu.email as customer_email
                FROM notes n
                JOIN users u ON n.user_id = u.id
                JOIN users cu ON cu.id = ?
                WHERE n.id = ? AND n.access_type = 'premium'
            ");
            $stmt->execute([$user_id, $note_id]);
            $note = $stmt->fetch();

            if (!$note) {
                throw new Exception('Materiał nie istnieje lub nie jest dostępny.');
            }

            // Create Stripe session
            $session = $this->stripeClient->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'pln',
                        'product_data' => [
                            'name' => $note['title'],
                            'description' => $note['subject'],
                        ],
                        'unit_amount' => (int)($note['premium_price'] * 100), // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'customer_email' => $note['customer_email'],
                'success_url' => $returnUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $returnUrl . '?cancelled=1',
                'metadata' => [
                    'note_id' => $note_id,
                    'user_id' => $user_id,
                    'teacher_id' => $note['teacher_id'],
                ],
            ]);

            return $session;
        } catch (\Exception $e) {
            throw new Exception('Błąd Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve session and process successful payment
     */
    public function processSuccessfulPayment($session_id) {
        try {
            $session = $this->stripeClient->checkout->sessions->retrieve($session_id);

            if ($session->payment_status !== 'paid') {
                throw new Exception('Płatność nie została potwierdzona.');
            }

            $metadata = $session->metadata;
            $note_id = (int)$metadata['note_id'];
            $user_id = (int)$metadata['user_id'];
            $teacher_id = (int)$metadata['teacher_id'];

            // Check if purchase already exists
            $stmt = $this->pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND note_id = ?");
            $stmt->execute([$user_id, $note_id]);
            if ($stmt->fetch()) {
                return ['success' => true, 'message' => 'Dostęp już przyznany.'];
            }

            // Fetch payment details
            $intent = $this->stripeClient->paymentIntents->retrieve($session->payment_intent);
            $charge = $intent->charges->data[0];

            // Fetch note, student, and teacher info
            $stmt = $this->pdo->prepare("
                SELECT n.title, n.premium_price, n.subject,
                       s.username as student_name, s.email as student_email,
                       t.username as teacher_name
                FROM notes n
                JOIN users s ON s.id = ?
                JOIN users t ON t.id = ?
                WHERE n.id = ?
            ");
            $stmt->execute([$user_id, $teacher_id, $note_id]);
            $paymentInfo = $stmt->fetch();

            // Create purchase record
            $stmt = $this->pdo->prepare("
                INSERT INTO purchases (user_id, note_id, amount, stripe_id, payment_status, paid_at)
                VALUES (?, ?, ?, ?, 'completed', datetime('now'))
            ");
            $stmt->execute([$user_id, $note_id, $paymentInfo['premium_price'], $charge->id]);

            // Send email notification
            $mailer = MailerService::getInstance();
            $mailer->sendPaymentNotification([
                'student_name' => $paymentInfo['student_name'],
                'student_email' => $paymentInfo['student_email'],
                'note_title' => $paymentInfo['title'],
                'teacher_name' => $paymentInfo['teacher_name'],
                'amount' => $paymentInfo['premium_price'],
                'stripe_id' => $charge->id,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return ['success' => true, 'message' => 'Płatność zrealizowana pomyślnie!'];
        } catch (\Exception $e) {
            throw new Exception('Błąd przetwarzania: ' . $e->getMessage());
        }
    }
}
