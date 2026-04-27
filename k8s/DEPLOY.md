# Deployment Guide — REP CORE FITNESS

Two deployment options are available. Pick one.

---

## Option A — Docker Compose (Recommended for single server)

### Step 1 — Set up Cloudflare Tunnel (one-time, ~5 min)

```bash
# Install cloudflared on your machine
winget install Cloudflare.cloudflared         # Windows
# brew install cloudflared                    # macOS

# Log in (opens browser)
cloudflared tunnel login

# Create the tunnel (do this ONCE — name is permanent)
cloudflared tunnel create gym-attendance
# → prints: Created tunnel gym-attendance with id xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx

# Copy credentials into the project
cp ~/.cloudflared/<TUNNEL_ID>.json docker/cloudflared/credentials.json

# Point your domain at the tunnel (you need a domain on Cloudflare)
cloudflared tunnel route dns gym-attendance gym.yourdomain.com
```

### Step 2 — Edit config files

1. **`docker/cloudflared/config.yml`** — replace `YOUR_TUNNEL_ID_HERE` and `gym.yourdomain.com`
2. **`.env.docker`** — replace `APP_URL` with `https://gym.yourdomain.com`
3. **`.env.docker`** — `APP_SECRET` is already regenerated (strong 64-char hex)

### Step 3 — Build and start

```bash
# Build images
docker compose build

# Start all services (app, nginx, mysql, cloudflare tunnel)
docker compose up -d

# Scale PHP-FPM workers (optional — nginx load-balances automatically)
docker compose up -d --scale app=3

# View logs
docker compose logs -f

# Check tunnel status
docker compose exec tunnel cloudflared tunnel info gym-attendance
```

### Your public URL
```
https://gym.yourdomain.com
```
Share this link with clients. It stays the same forever — even after reboots.

---

## Option B — Kubernetes

### Prerequisites
- kubectl configured for your cluster
- Your Docker images pushed to a registry
- A Cloudflare tunnel credentials file

### Step 1 — Build and push images

```bash
docker build -t YOUR_REGISTRY/gym-attendance:latest .
docker build -f docker/nginx/Dockerfile -t YOUR_REGISTRY/gym-attendance-nginx:latest .
docker push YOUR_REGISTRY/gym-attendance:latest
docker push YOUR_REGISTRY/gym-attendance-nginx:latest
```

### Step 2 — Prepare secrets

```bash
# Encode tunnel credentials
base64 -w0 ~/.cloudflared/<TUNNEL_ID>.json
# Paste the output into k8s/01-secrets.yaml → cloudflare-tunnel-credentials → credentials.json
```

Edit `k8s/01-secrets.yaml` and `k8s/02-configmap.yaml`:
- Replace `YOUR_TUNNEL_ID_HERE` with your actual tunnel UUID
- Replace `gym.yourdomain.com` with your real domain
- Replace `YOUR_REGISTRY` in `k8s/04-app.yaml` and `k8s/05-nginx.yaml`

### Step 3 — Apply manifests

```bash
kubectl apply -f k8s/00-namespace.yaml
kubectl apply -f k8s/01-secrets.yaml
kubectl apply -f k8s/02-configmap.yaml
kubectl apply -f k8s/03-mysql.yaml
kubectl apply -f k8s/04-app.yaml
kubectl apply -f k8s/05-nginx.yaml
kubectl apply -f k8s/06-cloudflare-tunnel.yaml
```

### Step 4 — Verify

```bash
kubectl get pods -n gym-app
kubectl logs -n gym-app deployment/cloudflared
```

### Your public URL
```
https://gym.yourdomain.com
```

---

## Security checklist (already done)

- [x] `test_smtp.php` removed (returns 404)
- [x] `SESSION_SECURE=true` — cookies require HTTPS
- [x] `APP_SECRET` regenerated to strong 64-char random hex
- [x] Database passwords rotated to strong random values
- [x] `SMTP_HOST` corrected to `smtp.gmail.com`
- [x] `TRUSTED_PROXIES` covers Docker/K8s pod network for real IP forwarding
- [x] nginx restores real client IP from `CF-Connecting-IP` (Cloudflare header)
- [x] nginx blocks `/src`, `/vendor`, `/storage`, `/docker`, dotfiles
- [x] Rate limiting: 5 req/min login, 30 req/min API, 120 req/min general
- [x] PHP files outside `public/` are blocked by `.htaccess` and nginx
- [x] MySQL not exposed outside Docker/K8s network
- [x] Cloudflare tunnel: no inbound ports open on your server at all
