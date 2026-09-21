# Scout Server — XAMPP Comms Server

Central coordination server for Docker scout bots (VM) + phone/laptop dashboard. Plain PHP 8 + MariaDB (XAMPP) + PDO, no framework.

## Structure
```
scout-server/
  index.php               # redirect -> dashboard.php
  dashboard.php           # responsive UI (polls every 2s)
  config/db.php           # PDO connection (env defaults)
  api/
    register.php          # POST {bot_id, proxy_email, poll_inbox, container_id}
    heartbeat.php         # POST {bot_id, state, sims_count, uptime, current_url}
    state.php             # GET ?bot_id=14  (or no param = all bots) | POST log
    command.php           # GET ?bot_id=14 (pending) | POST {bot_id, cmd, args}
    command_ack.php       # POST {command_id, status}
    notify.php            # POST {bot_id, type, message, details}
  sql/schema.sql          # CREATE TABLE bots, bot_logs, commands, notifications
  .htaccess
  README.md
```

## XAMPP Setup

1. **Copy to htdocs**
   ```
   C:\xampp\htdocs\scout-server  <- copy this folder
   # or
   xcopy "C:\Users\Lemuel Quaye\Documents\scouts\scout-server" "C:\xampp\htdocs\scout-server" /E /I
   ```
   Access via `http://localhost/scout-server/`  (redirects to dashboard).

2. **Create DB & import schema**
   - Start Apache + MySQL in XAMPP Control Panel
   - Open `http://localhost/phpmyadmin` → Create DB `scout_db` (utf8mb4)
   - Import → `sql/schema.sql`  (or via shell):
     ```
     C:\xampp\mysql\bin\mysql.exe -u root < sql\schema.sql
     # default XAMPP root has no password; if you set one use -p
     ```
   - If you use a different DB name/user, set env vars below.

3. **Configure DB & secrets**
   - Defaults: `DB_HOST=localhost` `DB_PORT=3306` `DB_NAME=scout_db` `DB_USER=root` `DB_PASS=""` `BOT_TOKEN=scout-secret`
   - For XAMPP env vars set via `C:\xampp\apache\conf\httpd.conf` or `.htaccess`:
     ```apache
     SetEnv DB_HOST localhost
     SetEnv DB_NAME scout_db
     SetEnv DB_USER root
     SetEnv DB_PASS ""
     SetEnv BOT_TOKEN scout-secret
     # optional telegram
     SetEnv TELEGRAM_BOT_TOKEN 123:ABC
     SetEnv TELEGRAM_CHAT_ID 123456789
     ```
   - Or set system env vars and restart Apache.
   - `config/db.php` also reads `getenv()` so you can edit defaults directly if not using env.

4. **Verify**
   - Visit `http://localhost/scout-server/dashboard.php` — should show "0 bots".
   - Test API with curl (from VM):
     ```
     curl -X POST http://HOST_IP/scout-server/api/register.php -H "Content-Type: application/json" -H "X-Bot-Token: scout-secret" -d "{\"bot_id\":14,\"proxy_email\":\"a@b.com\",\"poll_inbox\":\"inbox\",\"container_id\":\"abc123\"}"
     curl -X POST http://HOST_IP/scout-server/api/heartbeat.php -H "X-Bot-Token: scout-secret" -H "Content-Type: application/json" -d "{\"bot_id\":14,\"state\":\"running\",\"sims_count\":2,\"uptime\":\"5m\",\"current_url\":\"https://example.com\"}"
     curl http://HOST_IP/scout-server/api/state.php -H "X-Bot-Token: scout-secret"
     curl http://HOST_IP/scout-server/api/command.php?bot_id=14 -H "X-Bot-Token: scout-secret"
     ```

## Bot Integration (VM)

Bots must send header `X-Bot-Token: scout-secret` (or your `BOT_TOKEN`) on every API call.

- On start: `POST /api/register.php`
- Every 5-10s: `POST /api/heartbeat.php`
- Poll commands: `GET /api/command.php?bot_id=14` every 2-3s → execute → `POST /api/command_ack.php {command_id, status:"done"}`
- Notify: `POST /api/notify.php {bot_id, type:"NoSimsRegistered|suspended|otp_failed", message, details}`

CORS is open (`*`) for VM → host.

## Dashboard

- `dashboard.php` polls `api/state.php` every 2s, renders bots table with heartbeat age (green <15s, yellow <60s, red >60s)
- Click row → live logs + notifications
- Send `PAUSE|RESUME|RESTART|STOP|REFRESH` per bot or custom.

## Telegram (optional)

Set `TELEGRAM_BOT_TOKEN` + `TELEGRAM_CHAT_ID` env → `api/notify.php` forwards every notification to Telegram. Alternatively set `TELEGRAM_WEBHOOK_URL` to POST JSON to your own webhook.

## Troubleshooting

- `DB connection failed` → check `config/db.php` defaults / env / MariaDB running / DB imported
- `401 unauthorized` → missing/wrong `X-Bot-Token` header (default `scout-secret`)
- `404` on `/scout-server/api/*` → ensure folder is under `htdocs` and Apache `AllowOverride All` enabled (XAMPP default yes)
- Dashboard empty but bots report OK → check `bots` table in phpMyAdmin, verify bot_id int, check PHP error log `C:\xampp\apache\logs\error.log`
