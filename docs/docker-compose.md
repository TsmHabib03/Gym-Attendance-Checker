# Docker Compose Deployment

This setup runs the app with three services:
- `web`: Nginx (public HTTP entrypoint)
- `app`: PHP-FPM (application runtime)
- `db`: MySQL 8.4 (data store)

## 1. Prepare environment variables
1. Copy `.env.docker.example` to `.env.docker`.
2. Update secrets before first production use:
   - `APP_SECRET`
   - `DB_PASS`
   - `MYSQL_ROOT_PASSWORD`
   - SMTP and mail sender values
3. Keep these values matched:
   - `DB_NAME` <-> `MYSQL_DATABASE`
   - `DB_USER` <-> `MYSQL_USER`
   - `DB_PASS` <-> `MYSQL_PASSWORD`

## 2. Build and start
```bash
docker compose --env-file .env.docker up -d --build
```

## 3. Access points
- App: `http://localhost:8080`
- MySQL: available inside Docker network as `db:3306`

## 4. First startup behavior
- MySQL runs initialization scripts automatically on first boot:
  - `database/schema.sql`
  - `database/seed.sql`
- Seed admin credentials:
  - Username: `admin`
  - Password: `Admin@123`

Change the default admin password immediately.

## 5. Day-to-day commands
```bash
# Follow logs
docker compose --env-file .env.docker logs -f

# Stop services
docker compose --env-file .env.docker down

# Stop and remove volumes (destructive)
docker compose --env-file .env.docker down -v
```

If you get a Windows named pipe error like `//./pipe/dockerDesktopLinuxEngine`, start Docker Desktop first, then rerun the command.

## 6. Run scheduled scripts
Use host scheduler/cron to run inside the app container:

```bash
docker compose --env-file .env.docker exec -T app php scripts/send_expiry_reminders.php
docker compose --env-file .env.docker exec -T app php scripts/cleanup_rate_limits.php
```

## 7. Production notes
- Put TLS in front of `web` (reverse proxy or ingress) and set `SESSION_SECURE=true`.
- Restrict port exposure to only required endpoints.
- Back up the MySQL volume (`db_data`) regularly.
- Uploads and logs are persisted in Docker volumes:
  - `member_photos`
  - `checkin_photos`
  - `app_logs`
