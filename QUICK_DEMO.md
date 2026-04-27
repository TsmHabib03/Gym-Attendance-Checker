# 🚀 QUICK DEMO - 2 Minute Setup

## One-Command Demo Deployment

```bash
chmod +x demo-deploy.sh
./demo-deploy.sh
```

That's it! You'll get a **shareable Cloudflare tunnel link** in 2 minutes.

---

## What You'll Get

### ✅ Live Public Link (Random Domain)
```
Example: https://a1b2c3d4-efgh-ijkl-mnop-qrstuvwxyz.cfargotunnel.com
```

### ✅ Running Services
- Nginx web server
- MySQL database
- Cloudflare tunnel

### ✅ Default Credentials
```
Username: admin
Password: demo123
```

---

## Share with Client

Just copy the public link and share:

```
🔗 https://your-random-tunnel.cfargotunnel.com

Check out the new improvements!
```

---

## How It Works

1. **Spins up Kubernetes pods** for Nginx + MySQL
2. **Creates Cloudflare tunnel** instantly
3. **Generates public shareable link**
4. **Shows you the link immediately**

---

## Stop the Demo

When done:

```bash
# Delete everything
kubectl delete namespace gym-app-demo

# Remove tunnel
cloudflared tunnel delete gym-demo-<timestamp>
```

---

## Prerequisites

```bash
# Make sure you have these
✓ kubectl
✓ cloudflared (logged in with: cloudflared tunnel login)
✓ Kubernetes cluster running
```

---

**That's it! Run the script and share the link!** 🎉
