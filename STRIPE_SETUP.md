# YTI School Hub - Dokumentacja Płatności Stripe

## Setup Stripe

### 1. Klucze API Stripe

Uzyskaj klucze z https://dashboard.stripe.com/

```bash
# Testowe klucze (development)
STRIPE_PUBLIC_KEY=pk_test_xxxxxxxxxxxx
STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_test_xxxxxxxxxxxx
```

### 2. Zmienne środowiskowe (Docker)

Ustaw w `.env`:

```
STRIPE_PUBLIC_KEY=pk_test_xxxxxxxxxxxx
STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_test_xxxxxxxxxxxx
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
MAIL_FROM_ADDRESS=noreply@yti-school.pl
```

### 3. Instalacja Stripe SDK

```bash
docker compose exec app composer require stripe/stripe-php
```

## Flow Płatności

### 1. Uczeń klika "Zapłać" na materiał premium
↓
### 2. Przechodzi do `payment_checkout.php?note_id=X`
↓
### 3. StripePaymentHandler tworzy sesję checkout
↓
### 4. Redirect na https://checkout.stripe.com/...
↓
### 5. Użytkownik płaci kartą (Stripe secure)
↓
### 6. Succes redirect do `payment_checkout.php?session_id=...`
↓
### 7. StripePaymentHandler weryfikuje sesję
↓
### 8. Tworzy rekord `purchases` + wysyła email do jankom@eskp.pl
↓
### 9. Uczeń dostaje dostęp do materiału

## Email Notification

Admin (jankom@eskp.pl) otrzymuje:
- Imię ucznia + email
- Tytuł materiału + nauczyciel
- Kwotę transakcji
- Stripe Charge ID
- Datę i czas

## Testowanie

### Karty testowe Stripe:
```
4242 4242 4242 4242  (Success)
4000 0000 0000 0002  (Decline)
4000 0002 5000 3155  (3D Secure)
```
- Expiry: 12/25
- CVC: 123

## Webhook (Opcjonalnie dla produkcji)

```
POST /webhook/stripe.php
```

Webhook obsługuje:
- `checkout.session.completed` → Weryfikacja płatności
- `payment_intent.payment_failed` → Aktualizacja statusu

## Schema zmian

```sql
ALTER TABLE purchases ADD COLUMN stripe_id TEXT UNIQUE DEFAULT NULL;
ALTER TABLE purchases ADD COLUMN payment_status TEXT DEFAULT 'pending';
ALTER TABLE purchases ADD COLUMN paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
```
