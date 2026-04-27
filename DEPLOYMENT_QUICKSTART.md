# Quick Start: Deploy to Production with Cloudflare & Kubernetes

## 30-Second Overview

Deploy your app to a production-grade Kubernetes cluster with Cloudflare tunneling in 4 commands:

```bash
# 1. Set environment variables
export REGISTRY_URL="your-registry.io"
export REGISTRY_USERNAME="your-username"
export REGISTRY_PASSWORD="your-password"
export DOMAIN="gym.yourdomain.com"
export KUBECONFIG="~/.kube/config"

# 2. Run the deployment script
chmod +x deploy.sh
./deploy.sh

# 3. Follow the prompts (optional: build images and deploy)
# 4. Share the link from the output with your client!
```

---

## Step-by-Step Guide

### Prerequisites

- ✅ Docker installed (`docker --version`)
- ✅ kubectl installed (`kubectl version --client`)
- ✅ Kubernetes cluster running (`kubectl cluster-info`)
- ✅ Container registry account (Docker Hub, Cloudflare Container Registry, etc.)
- ✅ Cloudflare account with a domain

### Option 1: Automated Deployment (Recommended)

```bash
# Set your credentials
export REGISTRY_URL="docker.io"
export REGISTRY_USERNAME="yourname"
export REGISTRY_PASSWORD="your-password"
export DOMAIN="gym.yourdomain.com"

# Run the automated deployment
./deploy.sh
```

The script will:
1. ✓ Validate prerequisites
2. ✓ Build Docker images
3. ✓ Push to your registry
4. ✓ Create K8s secrets
5. ✓ Deploy all services
6. ✓ Wait for pods to be ready
7. ✓ Generate shareable link

### Option 2: Manual Deployment Steps

#### Step 1: Build & Push Docker Images

```bash
# Login to registry
docker login

# Build images
docker build -t your-registry/gym-attendance:latest .

# Push
docker push your-registry/gym-attendance:latest
```

#### Step 2: Update Kubernetes Manifests

```bash
# Replace placeholders in k8s manifests
sed -i 's|YOUR_REGISTRY|your-registry|g' k8s/*.yaml
sed -i 's|yourdomain.com|gym.yourdomain.com|g' k8s/*.yaml
```

#### Step 3: Deploy to Kubernetes

```bash
# Create namespace
kubectl create namespace gym-app

# Deploy
kubectl apply -f k8s/01-secrets.yaml
kubectl apply -f k8s/02-configmap.yaml
kubectl apply -f k8s/03-mysql.yaml
sleep 30

kubectl apply -f k8s/04-app.yaml
kubectl apply -f k8s/05-nginx.yaml
kubectl apply -f k8s/06-cloudflare-tunnel-enhanced.yaml
```

#### Step 4: Verify Deployment

```bash
# Check pods
kubectl get pods -n gym-app

# Check services
kubectl get svc -n gym-app

# Check deployments
kubectl get deployments -n gym-app
```

---

## Configuration

### Environment Variables

```bash
# Registry settings
REGISTRY_URL="docker.io"              # Your container registry
REGISTRY_USERNAME="your-username"     # Registry username
REGISTRY_PASSWORD="your-password"     # Registry password

# Kubernetes
KUBECONFIG="~/.kube/config"           # K8s config file

# Application
DOMAIN="gym.yourdomain.com"           # Your domain
NAMESPACE="gym-app"                   # K8s namespace
IMAGE_TAG="latest"                    # Image version
```

### Database Configuration

Edit `k8s/02-configmap.yaml`:

```yaml
DB_HOST: mysql
DB_USER: gym_user
DB_NAME: gym_db
DB_PORT: "3306"
```

Edit `k8s/01-secrets.yaml`:

```yaml
DB_PASS: "your-secure-password"       # Change this!
SMTP_PASSWORD: "your-email-password"  # Change this!
```

### Cloudflare Tunnel Setup

```bash
# 1. Install cloudflared
curl -L https://github.com/cloudflare/cloudflared/releases/download/2024.3.0/cloudflared-linux-amd64 -o cloudflared
chmod +x cloudflared

# 2. Create tunnel
./cloudflared tunnel create gym-app
export TUNNEL_ID=$(./cloudflared tunnel list | grep gym-app | awk '{print $1}')

# 3. Get credentials
./cloudflared tunnel token $TUNNEL_ID > tunnel-token.txt

# 4. Update secret
kubectl create secret generic cloudflare-tunnel-credentials \
  --from-file=credentials.json=~/.cloudflared/$TUNNEL_ID.json \
  -n gym-app
```

---

## Monitoring & Logs

### View Pod Status

```bash
# Watch pods in real-time
kubectl get pods -n gym-app -w

# Get pod details
kubectl describe pod <pod-name> -n gym-app

# View pod logs
kubectl logs -f <pod-name> -n gym-app
```

### View Application Logs

```bash
# App logs
kubectl logs -f deployment/gym-app -n gym-app

# Nginx logs
kubectl logs -f deployment/nginx -n gym-app

# Tunnel logs
kubectl logs -f deployment/cloudflare-tunnel -n gym-app
```

### Monitor Resources

```bash
# CPU and memory usage
kubectl top pods -n gym-app
kubectl top nodes

# Watch auto-scaling
kubectl get hpa -n gym-app -w
```

---

## Troubleshooting

### Images Not Pulling

```bash
# Check registry secret
kubectl get secrets -n gym-app
kubectl describe secret regcred -n gym-app

# Re-create secret
kubectl delete secret regcred -n gym-app
kubectl create secret docker-registry regcred \
  --docker-server=$REGISTRY_URL \
  --docker-username=$REGISTRY_USERNAME \
  --docker-password=$REGISTRY_PASSWORD \
  -n gym-app
```

### MySQL Not Starting

```bash
# Check MySQL pod
kubectl describe pod -l app=mysql -n gym-app

# Check MySQL logs
kubectl logs -l app=mysql -n gym-app

# Check PVC
kubectl get pvc -n gym-app
```

### Tunnel Not Connecting

```bash
# Check tunnel logs
kubectl logs -l app=cloudflare-tunnel -n gym-app

# Verify tunnel credentials
kubectl get secret cloudflare-tunnel-credentials -n gym-app

# Restart tunnel
kubectl rollout restart deployment/cloudflare-tunnel -n gym-app
```

### Pods Stuck in CrashLoop

```bash
# Get detailed pod info
kubectl describe pod <pod-name> -n gym-app

# Check recent events
kubectl get events -n gym-app --sort-by='.lastTimestamp'

# View logs for errors
kubectl logs --previous <pod-name> -n gym-app
```

---

## Testing the Deployment

### Test Application Availability

```bash
# Get public URL
echo "https://$DOMAIN"

# Test with curl
curl -I https://gym.yourdomain.com

# Test health endpoint
curl https://gym.yourdomain.com/health
```

### Test Rate Limiting

```bash
# Should get 429 (Too Many Requests) after limit
for i in {1..10}; do curl https://gym.yourdomain.com/login; done
```

### Load Testing

```bash
# Install Apache Bench
sudo apt-get install apache2-utils

# Run load test (100 concurrent requests, 1000 total)
ab -n 1000 -c 100 https://gym.yourdomain.com/
```

---

## Share with Client

Copy this link to share with your client:

```
🔗 Application URL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
https://gym.yourdomain.com

📊 Try it out!
1. Visit the link above
2. Login with provided credentials
3. Explore new features
4. Check performance metrics

🔐 Features Deployed:
✓ Production-grade security
✓ Auto-scaling (2-8 replicas)
✓ DDoS protection
✓ Rate limiting
✓ 99.9% uptime SLA
✓ Zero-downtime deployments
```

---

## Scaling & Performance

### Manual Scaling

```bash
# Scale app to 5 replicas
kubectl scale deployment/gym-app --replicas=5 -n gym-app

# Watch scaling
kubectl get deployment/gym-app -n gym-app -w
```

### Auto-scaling

The application automatically scales based on CPU usage:

```bash
# View HPA status
kubectl get hpa -n gym-app
kubectl describe hpa gym-app-hpa -n gym-app

# Watch auto-scaling happen
kubectl get hpa -n gym-app -w
```

Current thresholds:
- Min: 2 replicas
- Max: 8 replicas
- CPU threshold: 70%

---

## Updates & Rollbacks

### Deploy New Version

```bash
# Build and push new image
docker build -t your-registry/gym-attendance:v2 .
docker push your-registry/gym-attendance:v2

# Update deployment
kubectl set image deployment/gym-app \
  php-fpm=your-registry/gym-attendance:v2 \
  -n gym-app

# Watch rollout
kubectl rollout status deployment/gym-app -n gym-app
```

### Rollback to Previous Version

```bash
# View history
kubectl rollout history deployment/gym-app -n gym-app

# Rollback
kubectl rollout undo deployment/gym-app -n gym-app
```

---

## Security Checklist

Before sharing with client:

- [ ] Database passwords changed in `k8s/01-secrets.yaml`
- [ ] SMTP credentials configured
- [ ] Cloudflare WAF rules enabled
- [ ] Rate limiting tested
- [ ] SSL certificate valid
- [ ] DDoS protection enabled
- [ ] Admin password changed
- [ ] Backup schedule configured
- [ ] Monitoring alerts set up
- [ ] Security scan completed

---

## Next Steps

1. **Monitor**: Watch logs and metrics regularly
   ```bash
   kubectl logs -f deployment/gym-app -n gym-app
   ```

2. **Backup**: Configure automated backups
   ```bash
   # Enable daily snapshots to S3/R2
   ```

3. **Alert**: Set up notifications
   - Slack integration for deployments
   - PagerDuty for critical alerts
   - Email for system events

4. **Optimize**: Fine-tune performance
   - Enable CDN caching
   - Optimize images
   - Configure database indexes

5. **Document**: Keep deployment notes
   - Record any custom configurations
   - Document backup procedures
   - Note any issues encountered

---

## Support

For issues or questions:

1. Check Cloudflare Dashboard: https://dash.cloudflare.com
2. Review Kubernetes logs: `kubectl logs -f deployment/gym-app -n gym-app`
3. Consult the full guide: `CLOUDFLARE_DEPLOYMENT.md`

---

**Ready to deploy? Run this command:**

```bash
./deploy.sh
```

**Estimated deployment time: 5-10 minutes** ✨
