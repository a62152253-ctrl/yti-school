# YTI School Hub - Deployment Guide

## Deploy na Render (Free tier)

### Setup:

1. **Utwórz GitHub account**: https://github.com/signup
2. **Zaloguj na Render**: https://render.com (GitHub login)
3. **Połącz repo z Render** (auto deploy z każdym pushem)

### Zmienne środowiskowe (ustaw w Render dashboard):

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

### Build Command:
```bash
composer install
```

### Start Command:
```bash
vendor/bin/heroku-php-apache2 .
```

### Limitacje Free Tier:
- 750h/miesiąc (sypialnia po 15 min bez ruchu)
- 100GB/miesiąc transferu
- 1GB storage
- 1 concurrent process

### URL aplikacji:
```
https://yti-school.onrender.com
```

### Alternatywy:

**Railway** ($5/miesiąc kredyt):
- https://railway.app
- Pełny Docker support
- Lepszy dla PHP + SQLite

**Krok po kroku na Railway:**
1. GitHub login na https://railway.app
2. New Project → GitHub Repo
3. Connect → Deploy
4. Env variables w dashboard
5. Deploy automatycznie

### CI/CD (GitHub Actions):

Dodaj `.github/workflows/deploy.yml`:
```yaml
name: Deploy to Render
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: curl https://api.render.com/deploy/${{ secrets.RENDER_DEPLOY_HOOK }}
```

## Lokalne testowanie przed deployem:

```bash
docker compose up -d
php -S localhost:8000
# http://localhost:8000
```

## Problemy?

Jeśli Render się usypią:
1. **Upgrade na paid** ($7+)
2. **Albo Railway** ($5 kredyt)
3. **Albo VPS** (Linode $5, DigitalOcean $5)
