# 🚀 Deploy YTI School na Render - Step by Step

## Krok 1: GitHub Setup

```powershell
# Zainstaluj Git z https://git-scm.com

# Przejdź do folderu projektu
cd C:\XAMPP2\htdocs\yti

# Inicjalizuj Git
git init
git config user.email "jankom@eskp.pl"
git config user.name "YTI School Hub"

# Dodaj wszystkie pliki
git add .

# Utwórz commit
git commit -m "Initial commit: YTI School with Stripe + Email Reset"
```

## Krok 2: Utwórz GitHub Repo

1. Idź na https://github.com/new
2. Wpisz **Repository name**: `yti-school`
3. Wybierz **Public** (żeby Render mógł czytać)
4. Kliknij **Create repository**
5. GitHub pokaże Ci komendy - skopiuj je

```powershell
# Skopiuj z GitHub (zamień URL poniżej!)
git remote add origin https://github.com/YOUR_USERNAME/yti-school.git
git branch -M main
git push -u origin main
```

## Krok 3: Render Deployment

1. Idź na https://render.com
2. Zaloguj się via GitHub (kliknij "Continue with GitHub")
3. Autoryzuj Render dostęp do swoich reposów
4. Kliknij **New +** → **Web Service**
5. Wybierz **yti-school** repo
6. Settings:
   - **Name**: yti-school
   - **Environment**: PHP
   - **Build Command**: `composer install`
   - **Start Command**: `vendor/bin/heroku-php-apache2 .`
   - **Instance Type**: Free

7. Kliknij **Advanced** i dodaj **Environment Variables**:

```
APP_DEBUG=false
STRIPE_PUBLIC_KEY=[REDACTED]v5RwCkDUqHGW88dZAypoKelfs00eGrbPSOY
STRIPE_SECRET_KEY=[REDACTED]4eTr8YggNd1IeRA45srz4HCac00ceqXQ0HJ
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=jankom@eskp.pl
SMTP_PASS=your-gmail-app-password
MAIL_FROM_ADDRESS=noreply@yti-school.pl
```

8. Kliknij **Deploy** lub **Create Web Service**

## Krok 4: Czekaj na Deploy

- Render będzie buildować (~3-5 minut)
- Log pojawi się w dashboard
- Po skończeniu - URL: `https://yti-school.onrender.com`

## Krok 5: Przyszłe deploye

Wszystko się dzieje automatycznie! Po każdym `git push`:

```powershell
git add .
git commit -m "Nowa funkcja: XYZ"
git push
```

→ Render automatycznie deploye nową wersję!

## 🆘 Problemy?

**Render się usypia:**
- Free tier = 750h/miesiąc
- Sypialnia po 15 min bez ruchu
- Rozwiązanie: Upgrade na $7/miesiąc LUB Railway

**Chcesz Railway zamiast?**
- https://railway.app
- GitHub login
- Connect repo
- Deploy (lepiej dla PHP)

**Błąd compose/database?**
- Render używa `/tmp` do tymczasowych plików
- Zmień `define('DB_FILE', ...)` na environment path

## ✅ Po deployem

- URL: https://yti-school.onrender.com
- Login: student@domena.com / admin_password
- Stripe payments: LIVE (testowe karty działają!)
- Reset hasła: LIVE (emaile na Gmail)
- Płatności: Janusz dostaje emaile!
