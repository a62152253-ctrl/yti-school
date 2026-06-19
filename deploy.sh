#!/bin/bash

# YTI School - GitHub + Render Deployment Script

echo "🚀 YTI School - Deployment Setup"
echo "=================================="
echo ""

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo "❌ Git nie jest zainstalowany. Zainstaluj go z https://git-scm.com"
    exit 1
fi

# Initialize git
echo "📝 Inicjalizuję Git repository..."
git init
git config user.email "jankom@eskp.pl"
git config user.name "YTI School"

# Add files
echo "📦 Dodaję pliki do gita..."
git add .

# Initial commit
echo "💾 Tworzę initial commit..."
git commit -m "Initial commit: YTI School Hub with Stripe payments & email reset"

# Show GitHub setup instructions
echo ""
echo "✅ Git repository zainicjalizowany!"
echo ""
echo "Następne kroki:"
echo "1. Utwórz nowe repo na GitHub: https://github.com/new"
echo "2. Skopiuj URL repo (np. https://github.com/yourname/yti-school)"
echo "3. Uruchom w terminalu:"
echo ""
echo "   git remote add origin <URL>"
echo "   git branch -M main"
echo "   git push -u origin main"
echo ""
echo "4. Zaloguj na Render: https://render.com"
echo "5. New Web Service → GitHub"
echo "6. Połącz swój yti-school repo"
echo "7. Settings:"
echo "   - Runtime: PHP"
echo "   - Build Command: composer install"
echo "   - Start Command: vendor/bin/heroku-php-apache2 ."
echo ""
echo "8. Dodaj Environment Variables (Settings → Environment):"
echo "   - APP_DEBUG: false"
echo "   - STRIPE_PUBLIC_KEY: [REDACTED]v5RwCkDUqHGW88dZAypoKelfs00eGrbPSOY"
echo "   - STRIPE_SECRET_KEY: [REDACTED]4eTr8YggNd1IeRA45srz4HCac00ceqXQ0HJ"
echo "   - SMTP_HOST: smtp.gmail.com"
echo "   - SMTP_PORT: 587"
echo "   - SMTP_USER: jankom@eskp.pl"
echo "   - SMTP_PASS: your-gmail-app-password"
echo "   - MAIL_FROM_ADDRESS: noreply@yti-school.pl"
echo ""
echo "9. Deploy!"
echo ""
