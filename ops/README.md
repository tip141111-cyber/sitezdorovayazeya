# Миграция и серверные настройки

Эта папка хранит настройки, которые нужны, чтобы перенести сайт на другой сервер и не собирать конфигурацию заново.

## Что внутри

- `docker-compose.yml` - контейнерный вариант запуска сайта через nginx и PHP-FPM.
- `nginx/zdorovzeya.conf` - nginx-конфиг с HTTPS, заголовками безопасности, rate limit, limit_conn и аварийной страницей.
- `fail2ban/nginx-limit-req.local` - jail для блокировки частого попадания в nginx rate limit.
- `systemd/nginx-override.conf` - автоперезапуск и базовые лимиты nginx.
- `systemd/php74-fpm-override.conf` - автоперезапуск и базовые лимиты PHP-FPM.
- `php/www-hardening.conf` - рекомендуемые значения PHP-FPM pool для маленького сайта.
- `ufw-rules.txt` - список портов, которые должны быть открыты.

## Текущая схема сервера

Сайт работает в `/var/www/zdorovzeya`, nginx отдает HTML/CSS/изображения, PHP-FPM обрабатывает только `/send.php`.

Открытые порты:

- `22/tcp` - SSH.
- `80/tcp` - HTTP редирект на HTTPS.
- `443/tcp` - HTTPS сайт.
- `34314/udp` - Amnezia VPN.

## Быстрый переезд на новый VPS без Docker

1. Установить пакеты:

```bash
apt update
apt install -y nginx php-fpm git ufw fail2ban certbot python3-certbot-nginx
```

2. Склонировать сайт:

```bash
git clone https://github.com/tip141111-cyber/sitezdorovayazeya.git /var/www/zdorovzeya
chown -R www-data:www-data /var/www/zdorovzeya
```

3. Положить nginx-конфиг:

```bash
cp ops/nginx/zdorovzeya.conf /etc/nginx/sites-available/zdorovzeya
ln -s /etc/nginx/sites-available/zdorovzeya /etc/nginx/sites-enabled/zdorovzeya
nginx -t
systemctl reload nginx
```

4. Получить сертификат:

```bash
certbot --nginx -d zdorovyezeya.ru -d www.zdorovyezeya.ru
```

5. Включить firewall:

```bash
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 34314/udp
ufw --force enable
```

6. Включить fail2ban:

```bash
cp ops/fail2ban/nginx-limit-req.local /etc/fail2ban/jail.d/nginx-limit-req.local
systemctl enable --now fail2ban
systemctl restart fail2ban
```

## Docker-вариант

Docker-вариант удобен для тестового переезда и изоляции сайта. Почтовую отправку через `mail()` внутри контейнера нужно отдельно связать с SMTP-реле или заменить на отправку через Telegram/CRM/API.

```bash
docker compose -f ops/docker-compose.yml up -d
```
