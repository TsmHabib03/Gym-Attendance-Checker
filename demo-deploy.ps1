# Demo Deployment - PowerShell Version
# Quick Cloudflare Tunnel Setup

param(
    [string]$Namespace = "gym-app-demo"
)

Write-Host "`n================================`n    DEMO DEPLOYMENT`n================================`n"

# Check prerequisites
Write-Host "[INFO] Checking prerequisites..."

# Check kubectl
try {
    $null = kubectl version --client 2>&1
    Write-Host "[OK] kubectl found"
} catch {
    Write-Host "[ERROR] kubectl not found"
    exit 1
}

# Check cloudflared
try {
    $null = cloudflared --version 2>&1
    Write-Host "[OK] cloudflared found"
} catch {
    Write-Host "[ERROR] cloudflared not found"
    exit 1
}

# Check K8s connection
try {
    $null = kubectl cluster-info 2>&1
    Write-Host "[OK] Kubernetes connected`n"
} catch {
    Write-Host "[ERROR] Cannot connect to Kubernetes"
    exit 1
}

# Create namespace
Write-Host "[INFO] Creating namespace: $Namespace..."
kubectl create namespace $Namespace --dry-run=client -o yaml | kubectl apply -f - 2>$null
Write-Host "[OK] Namespace created`n"

# Deploy MySQL
Write-Host "[INFO] Deploying MySQL..."
$mysqlYaml = @'
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: mysql-data
  namespace: NAMESPACE_PLACEHOLDER
spec:
  accessModes: ["ReadWriteOnce"]
  resources:
    requests:
      storage: 5Gi
---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: mysql
  namespace: NAMESPACE_PLACEHOLDER
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
  namespace: NAMESPACE_PLACEHOLDER
spec:
  selector:
    app: mysql
  ports:
    - port: 3306
      targetPort: 3306
'@

$mysqlYaml = $mysqlYaml -replace "NAMESPACE_PLACEHOLDER", $Namespace
$mysqlYaml | kubectl apply -f - 2>$null
Write-Host "[OK] MySQL deployed`n"

Start-Sleep -Seconds 10

# Deploy Nginx
Write-Host "[INFO] Deploying Nginx..."
$nginxYaml = @'
apiVersion: apps/v1
kind: Deployment
metadata:
  name: nginx
  namespace: NAMESPACE_PLACEHOLDER
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
            - name: html
              mountPath: /usr/share/nginx/html
      volumes:
        - name: html
          emptyDir: {}
---
apiVersion: v1
kind: Service
metadata:
  name: nginx-service
  namespace: NAMESPACE_PLACEHOLDER
spec:
  selector:
    app: nginx
  ports:
    - port: 80
      targetPort: 80
'@

$nginxYaml = $nginxYaml -replace "NAMESPACE_PLACEHOLDER", $Namespace
$nginxYaml | kubectl apply -f - 2>$null
Write-Host "[OK] Nginx deployed`n"

# Check Cloudflare auth
Write-Host "[INFO] Checking Cloudflare authentication..."
$tunnels = cloudflared tunnel list 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERROR] Please login to Cloudflare:"
    Write-Host "  cloudflared tunnel login"
    exit 1
}
Write-Host "[OK] Cloudflare authenticated`n"

# Create tunnel
Write-Host "[INFO] Creating Cloudflare tunnel..."
$timestamp = Get-Date -Format "yyyyMMddHHmmss"
$tunnelName = "gym-demo-$timestamp"

try {
    cloudflared tunnel create $tunnelName 2>$null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "[ERROR] Failed to create tunnel"
        exit 1
    }
} catch {
    Write-Host "[ERROR] Failed to create tunnel"
    exit 1
}

# Get tunnel ID
$tunnelList = cloudflared tunnel list 2>&1
$tunnelLine = $tunnelList | Select-String $tunnelName | Select-Object -First 1
$tunnelID = ($tunnelLine -split '\s+')[0]

if (-not $tunnelID) {
    Write-Host "[ERROR] Failed to get tunnel ID"
    exit 1
}

Write-Host "[OK] Tunnel created: $tunnelID`n"

# Get token
Write-Host "[INFO] Getting tunnel token..."
$tunnelToken = cloudflared tunnel token $tunnelID 2>&1
Write-Host "[OK] Token obtained`n"

# Deploy tunnel
Write-Host "[INFO] Deploying tunnel to Kubernetes..."
$tunnelYaml = @"
apiVersion: apps/v1
kind: Deployment
metadata:
  name: cloudflare-tunnel
  namespace: $Namespace
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
            - $tunnelToken
          resources:
            requests:
              cpu: 50m
              memory: 64Mi
            limits:
              cpu: 100m
              memory: 128Mi
"@

$tunnelYaml | kubectl apply -f - 2>$null
Write-Host "[OK] Tunnel deployed`n"

# Wait for connection
Write-Host "[INFO] Waiting for tunnel to connect..."
Start-Sleep -Seconds 5

$publicUrl = "https://$tunnelID.cfargotunnel.com"

# Display results
Write-Host "================================"
Write-Host "    DEMO IS LIVE!"
Write-Host "================================`n"

Write-Host "PUBLIC LINK:"
Write-Host "$publicUrl`n"

Write-Host "TUNNEL DETAILS:"
Write-Host "  ID: $tunnelID"
Write-Host "  Name: $tunnelName"
Write-Host "  Namespace: $Namespace`n"

Write-Host "CREDENTIALS:"
Write-Host "  Username: admin"
Write-Host "  Password: demo123`n"

Write-Host "TO STOP THE DEMO:"
Write-Host "  kubectl delete namespace $Namespace"
Write-Host "  cloudflared tunnel delete $tunnelName`n"

Write-Host "SHARE THIS LINK:"
Write-Host "$publicUrl`n"

# Save info
$demoInfo = @"
DEMO DEPLOYMENT INFO

PUBLIC LINK: $publicUrl
TUNNEL ID: $tunnelID
TUNNEL NAME: $tunnelName
NAMESPACE: $Namespace

CREDENTIALS:
  Username: admin
  Password: demo123

TO STOP:
  kubectl delete namespace $Namespace
  cloudflared tunnel delete $tunnelName

Created: $(Get-Date)
"@

$demoInfo | Out-File -FilePath "$env:TEMP\demo-info.txt" -Force -Encoding UTF8
Write-Host "Info saved to: $env:TEMP\demo-info.txt"
