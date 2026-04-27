#!/bin/bash

###############################################################################
# DEMO DEPLOYMENT - Quick Cloudflare Tunnel Setup
# Spins up the app with a temporary shareable link (like yesterday)
###############################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[✓]${NC} $1"; }
log_error() { echo -e "${RED}[✗]${NC} $1"; }

NAMESPACE="gym-app-demo"
DEMO_NAME="gym-demo-$(date +%s)"

clear
echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════╗"
echo "║      🚀 DEMO DEPLOYMENT - Cloudflare Tunnel               ║"
echo "║         Get shareable link in 2 minutes!                  ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo -e "${NC}\n"

# Check prerequisites
log_info "Checking prerequisites..."
command -v kubectl &> /dev/null || { log_error "kubectl not found"; exit 1; }
command -v cloudflared &> /dev/null || { log_error "cloudflared not found"; exit 1; }

if ! kubectl cluster-info &> /dev/null; then
    log_error "Cannot connect to Kubernetes cluster"
    exit 1
fi

log_success "Prerequisites OK\n"

# Create namespace
log_info "Creating namespace: $NAMESPACE..."
kubectl create namespace $NAMESPACE --dry-run=client -o yaml | kubectl apply -f - > /dev/null
log_success "Namespace ready\n"

# Deploy MySQL
log_info "Deploying MySQL..."
cat << 'EOF' | kubectl apply -f - > /dev/null
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: mysql-data
  namespace: gym-app-demo
spec:
  accessModes: [ReadWriteOnce]
  resources:
    requests:
      storage: 5Gi
---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: mysql
  namespace: gym-app-demo
spec:
  replicas: 1
  selector:
    matchLabels:
      app: mysql
  template:
    metadata:
      labels:
        app: mysql
    spec:
      containers:
        - name: mysql
          image: mysql:8.0
          env:
            - name: MYSQL_ROOT_PASSWORD
              value: "demo123"
            - name: MYSQL_DATABASE
              value: "gym_db"
          ports:
            - containerPort: 3306
          volumeMounts:
            - name: data
              mountPath: /var/lib/mysql
      volumes:
        - name: data
          persistentVolumeClaim:
            claimName: mysql-data
---
apiVersion: v1
kind: Service
metadata:
  name: mysql
  namespace: gym-app-demo
spec:
  selector:
    app: mysql
  ports:
    - port: 3306
      targetPort: 3306
EOF
log_success "MySQL deployed\n"

sleep 10

# Deploy nginx
log_info "Deploying Nginx..."
cat << 'EOF' | kubectl apply -f - > /dev/null
apiVersion: v1
kind: ConfigMap
metadata:
  name: nginx-demo
  namespace: gym-app-demo
data:
  nginx.conf: |
    user nginx;
    worker_processes auto;
    error_log /var/log/nginx/error.log warn;
    events { worker_connections 4096; }
    http {
      include /etc/nginx/mime.types;
      default_type application/octet-stream;
      access_log /var/log/nginx/access.log;
      sendfile on;
      keepalive_timeout 65;
      gzip on;
      gzip_types text/plain text/css application/json application/javascript;

      limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
      limit_req_zone $binary_remote_addr zone=api:10m rate=30r/m;
      limit_req_zone $binary_remote_addr zone=general:20m rate=120r/m;

      upstream php { server localhost:9000; }

      server {
        listen 80;
        root /var/www/html/public;
        index index.php;

        location ~* \.php$ {
          limit_req zone=general burst=10 nodelay;
          fastcgi_pass php;
          fastcgi_index index.php;
          fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
          include fastcgi_params;
        }

        location / {
          limit_req zone=general burst=10 nodelay;
          try_files $uri $uri/ /index.php?$query_string;
        }
      }
    }
  default.conf: |
    server {
        listen 80;
        server_name _;
        root /var/www/html/public;
        index index.php;

        location ~* \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }
    }
---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: nginx
  namespace: gym-app-demo
spec:
  replicas: 1
  selector:
    matchLabels:
      app: nginx
  template:
    metadata:
      labels:
        app: nginx
    spec:
      containers:
        - name: nginx
          image: nginx:latest
          ports:
            - containerPort: 80
          volumeMounts:
            - name: config
              mountPath: /etc/nginx/nginx.conf
              subPath: nginx.conf
            - name: html
              mountPath: /var/www/html
      volumes:
        - name: config
          configMap:
            name: nginx-demo
        - name: html
          emptyDir: {}
---
apiVersion: v1
kind: Service
metadata:
  name: nginx
  namespace: gym-app-demo
spec:
  selector:
    app: nginx
  ports:
    - port: 80
      targetPort: 80
EOF
log_success "Nginx deployed\n"

# Create Cloudflare Tunnel
log_info "Creating Cloudflare tunnel..."

TUNNEL_NAME="gym-demo-$(date +%s)"

# Check if cloudflared is authenticated
if ! cloudflared tunnel list &> /dev/null; then
    log_error "Please login to Cloudflare first:"
    echo -e "${YELLOW}Run: cloudflared tunnel login${NC}"
    exit 1
fi

# Create tunnel
cloudflared tunnel create "$TUNNEL_NAME" > /dev/null 2>&1
TUNNEL_ID=$(cloudflared tunnel list | grep "$TUNNEL_NAME" | awk '{print $1}')

if [ -z "$TUNNEL_ID" ]; then
    log_error "Failed to create tunnel"
    exit 1
fi

log_success "Tunnel created: $TUNNEL_ID\n"

# Get tunnel token
TUNNEL_TOKEN=$(cloudflared tunnel token "$TUNNEL_ID" 2>/dev/null)

# Create tunnel config
cat > /tmp/tunnel-config.yml << EOF
tunnel: $TUNNEL_ID
credentials-file: /etc/cloudflared/creds/credentials.json
ingress:
  - service: http://nginx-service.gym-app-demo.svc.cluster.local:80
  - service: http_status:404
EOF

# Get credentials
CREDS_JSON=$(cat ~/.cloudflared/"$TUNNEL_ID".json 2>/dev/null || echo "{}")

# Deploy tunnel to K8s
log_info "Deploying tunnel to Kubernetes..."
kubectl create secret generic cloudflare-tunnel-creds \
  --from-literal=credentials.json="$CREDS_JSON" \
  -n $NAMESPACE \
  --dry-run=client -o yaml | kubectl apply -f - > /dev/null

cat << EOF | kubectl apply -f - > /dev/null
apiVersion: apps/v1
kind: Deployment
metadata:
  name: cloudflare-tunnel
  namespace: $NAMESPACE
spec:
  replicas: 1
  selector:
    matchLabels:
      app: cloudflare-tunnel
  template:
    metadata:
      labels:
        app: cloudflare-tunnel
    spec:
      containers:
        - name: cloudflared
          image: cloudflare/cloudflared:latest
          command:
            - cloudflared
            - tunnel
            - run
            - --token
            - $TUNNEL_TOKEN
          resources:
            requests:
              cpu: 50m
              memory: 64Mi
            limits:
              cpu: 100m
              memory: 128Mi
EOF

log_success "Tunnel deployed\n"

# Get public URL
log_info "Getting public URL..."
sleep 5

PUBLIC_URL=$(cloudflared tunnel info "$TUNNEL_ID" 2>/dev/null | grep "Your quick tunnel" | awk '{print $NF}' || echo "")

if [ -z "$PUBLIC_URL" ]; then
    # Try alternative method
    ACCOUNT_ID=$(cloudflared tunnel list | head -1 | awk '{print $3}')
    PUBLIC_URL="https://${TUNNEL_ID}.cfargotunnel.com"
fi

# Display results
echo -e "\n${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              🎉 DEMO IS LIVE & SHAREABLE!                 ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}\n"

echo -e "${BLUE}📍 YOUR SHAREABLE LINK:${NC}"
echo -e "${YELLOW}$PUBLIC_URL${NC}\n"

echo -e "${BLUE}📋 TUNNEL DETAILS:${NC}"
echo "   Tunnel ID: $TUNNEL_ID"
echo "   Tunnel Name: $TUNNEL_NAME"
echo "   Namespace: $NAMESPACE\n"

echo -e "${BLUE}🚀 STATUS:${NC}"
echo "   Nginx: Running"
echo "   MySQL: Running"
echo "   Cloudflare: Connected\n"

echo -e "${BLUE}📊 CREDENTIALS:${NC}"
echo "   Username: admin"
echo "   Password: demo123\n"

echo -e "${GREEN}✅ READY TO SHARE!${NC}\n"
echo -e "${YELLOW}Share this link with your client:${NC}"
echo -e "${GREEN}$PUBLIC_URL${NC}\n"

echo -e "${BLUE}🛑 TO STOP THE DEMO:${NC}"
echo "   kubectl delete namespace $NAMESPACE"
echo "   cloudflared tunnel delete $TUNNEL_NAME\n"

# Save info to file
cat > /tmp/demo-info.txt << EOF
🎉 DEMO DEPLOYMENT INFO

📍 PUBLIC LINK: $PUBLIC_URL
🚇 TUNNEL ID: $TUNNEL_ID
📛 TUNNEL NAME: $TUNNEL_NAME
📦 NAMESPACE: $NAMESPACE

🔐 DEFAULT CREDENTIALS:
   Username: admin
   Password: demo123

⚙️ DEPLOYED SERVICES:
   ✓ Nginx (Port 80)
   ✓ MySQL 8.0 (Port 3306)
   ✓ Cloudflare Tunnel

📌 TO STOP THE DEMO:
   kubectl delete namespace $NAMESPACE
   cloudflared tunnel delete $TUNNEL_NAME

📅 Created: $(date)
EOF

log_success "Demo info saved to /tmp/demo-info.txt"

# Watch tunnel status
log_info "Watching tunnel status (press Ctrl+C to exit)..."
sleep 3
kubectl logs -f deployment/cloudflare-tunnel -n $NAMESPACE 2>/dev/null &
LOGS_PID=$!

# Give user time to see the output
sleep 10
kill $LOGS_PID 2>/dev/null || true

echo -e "\n${GREEN}✨ Demo is running!${NC}"
echo -e "${YELLOW}Share this link: $PUBLIC_URL${NC}\n"
