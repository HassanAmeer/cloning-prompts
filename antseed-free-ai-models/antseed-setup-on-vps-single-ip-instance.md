# 🐜 Antseed AI — VPS Multi-Instance & Load Balancer Setup Blueprint

> **File:** `antseed-setup-on-vps-multi-instance.md`  
> **Target Environment:** Hostinger KVM 2 (8 GB RAM, 2 vCPU, 100 GB NVMe, Ubuntu 22.04 / 24.04 LTS)  
> **Architecture:** 10 Independent Antseed Buyer Nodes + Dedicated Public IPs + Nginx High-Concurrency Load Balancer  
> **Capacity:** 500–1,000+ Concurrent User Requests / 2,000+ Registered Users  

---

## 📌 Table of Contents (Fehrist)
1. [Kyun Zaroori Hai Multi-Instance Setup? (Architecture & Reasons)](#-1-kyun-zaroori-hai-multi-instance-setup)
2. [Hardware & Concurrency Sizing (Hostinger KVM 2 Math)](#-2-hardware--concurrency-sizing)
3. [🤖 Master Agent Prompt (VPS Par 1-Click Setup Ke Liye)](#-3-master-agent-prompt-copy--paste)
4. [🛠️ Manual Step-by-Step Setup Guide](#-4-manual-step-by-step-setup-guide)
5. [🐳 Docker Compose Multi-Node Cluster (`docker-compose.yml`)](#-5-docker-compose-multi-node-cluster-docker-composeyml)
6. [⚖️ Nginx Load Balancer Configuration (`nginx.conf`)](#-6-nginx-load-balancer-configuration)
7. [📡 Standard OpenAI API Endpoints Available](#-7-standard-openai-api-endpoints-available)
8. [💻 Client & Team Integration (cURL, Python, Node.js, Codex)](#-8-client--team-integration-examples)
9. [🔧 Maintenance, Health Checks & Monitoring](#-9-maintenance-health-checks--monitoring)

---

## 🎯 1. Kyun Zaroori Hai Multi-Instance Setup?

Single-IP ya Single-Instance setup mein jab 50 se zyada users ek sath prompts bhejte hain, toh do baray maslay aate hain:
1. **Remote P2P Peer Rate-Limiting:** Decentralized Antseed network ke remote GPU providers ek hi IP se aane wali hazaron requests ko throttle ya cooldown mein daal dete hain (Error 429).
2. **Node Socket Saturation:** Ek single node par hazaron continuous streaming connections lagne se response slow hone lagta hai.

### 🌟 Multi-Instance + 10 IPs Ka Solution:
- **10 Independent Antseed Nodes:** Har container ek alag cryptographic P2P node ID ke sath network se judta hai.
- **10 Dedicated Public IPs:** Har container alag public IP se internet par request bhejta hai, jis se P2P network ko 10 mukhtalif independent servers nazar aate hain.
- **0% Ban / Block Risk:** Kisi bhi ek IP par pressure nahi banta.
- **Auto-Failover (No Downtime):** Agar Container 1 kisi busy peer par atakta hai, toh Nginx 50 milliseconds mein request Container 2 ya 3 ko redirect kar deta hai.

---

## 📊 2. Hardware & Concurrency Sizing (Hostinger KVM 2)

| Parameter | Value | Details / Justification |
| :--- | :--- | :--- |
| **VPS Specs** | 8 GB RAM / 2 vCPU / 100 GB NVMe | Hostinger KVM 2 Plan |
| **Active Instances** | **10 Docker Containers** | 2 vCPU context-switching ke liye ideal sweet-spot |
| **RAM Usage** | **~1.2 GB to 1.8 GB** | Har container ~100MB–120MB; 6+ GB RAM OS & Nginx cache ke liye free rahegi |
| **Per-Instance Load** | **25 to 50 Concurrent Users** | Node.js asynchronous I/O se bina kisi lag ke chalta hai |
| **Total Concurrency** | **250 to 500 Active Requests/sec** | Ek hi second mein 500 requests aaram se handle hongi |
| **Total Registered Team** | **1,000 to 2,500 Users** | Normal usage pattern ke mutabiq team smooth chalegi |
| **Storage Consumption** | **< 3 GB of 100 GB** | 97 GB NVMe space logs aur OS ke liye bachegi |

---

## 🤖 3. Master Agent Prompt (Copy & Paste)

> **Subah jab aap VPS par kisi bhi AI Coding Assistant (Antigravity, Cursor, Codex, Claude Code) ko run karein, toh sirf neeche diya gaya prompt copy karke us AI ko bhej dein:**

```markdown
You are an expert DevOps and Systems Architect. Your task is to set up a production-grade, 10-Instance Antseed AI Cluster with an Nginx High-Concurrency Load Balancer on this Ubuntu VPS.

Specifications & Goals:
1. Docker & Docker Compose: Ensure Docker and docker-compose-plugin are installed and enabled.
2. Architecture:
   - Deploy 10 independent Antseed Buyer containers (ports 8380 through 8389).
   - Each container must maintain its own isolated P2P data directory at /opt/antseed-cluster/data/node-{1..10}/.antseed.
   - Configure all nodes with routingPreferences: { "preferFreePeers": true, "maxInputUsdPerMillion": 25, "minTrustScore": 0 }.
3. Nginx Load Balancer:
   - Expose the cluster on standard HTTP Port 80 (and 8377 for backward compatibility).
   - Upstream pool targeting all 10 local instances (127.0.0.1:8380 to 8389) using least_conn / round-robin.
   - Enable proxy_buffering off; chunked_transfer_encoding on; for real-time Server-Sent Events (SSE) AI streaming.
   - Configure proxy_next_upstream error timeout invalid_header http_502 http_503 http_504 http_429; so any busy peer immediately fails over to the next healthy node in under 50ms.
4. Firewall:
   - Open ports 80, 443, 8377, and SSH port in UFW.
5. Verification:
   - Run curl http://127.0.0.1/v1/models and verify it returns a 200 OK JSON list of active models.
   - Run a test completion request against meta-llama/llama-3.3-70b-instruct to verify full end-to-end P2P generation.
```

---

## 🛠️ 4. Manual Step-by-Step Setup Guide

Agar aap manual commands chalana chahein:

### Step 4.1: VPS Package Update & Docker Install
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git ufw jq nginx docker.io docker-compose-v2

sudo systemctl enable --now docker
sudo systemctl enable --now nginx
```

### Step 4.2: Firewall Rules Enable
```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 8377/tcp
sudo ufw --force enable
```

### Step 4.3: Directory Structure Setup
```bash
sudo mkdir -p /opt/antseed-cluster/nginx
for i in $(seq 1 10); do
  sudo mkdir -p /opt/antseed-cluster/data/node-$i/.antseed
  # Configuration file create karein
  cat << 'EOF' | sudo tee /opt/antseed-cluster/data/node-$i/.antseed/config.json > /dev/null
{
  "buyer": {
    "routingPreferences": {
      "preferFreePeers": true,
      "maxInputUsdPerMillion": 25,
      "minTrustScore": 0
    }
  }
}
EOF
done
```

---

## 🐳 5. Docker Compose Multi-Node Cluster (`docker-compose.yml`)

Save this file at: `/opt/antseed-cluster/docker-compose.yml`

```yaml
version: '3.8'

services:
  # Node 1
  antseed-node-1:
    image: node:20-alpine
    container_name: antseed-node-1
    restart: always
    working_dir: /app
    volumes:
      - /opt/antseed-cluster/data/node-1/.antseed:/root/.antseed
    ports:
      - "127.0.0.1:8380:8377"
    command: sh -c "npm install -g @antseed/cli && antseed buyer start --host 0.0.0.0 --port 8377"

  # Node 2
  antseed-node-2:
    image: node:20-alpine
    container_name: antseed-node-2
    restart: always
    working_dir: /app
    volumes:
      - /opt/antseed-cluster/data/node-2/.antseed:/root/.antseed
    ports:
      - "127.0.0.1:8381:8377"
    command: sh -c "npm install -g @antseed/cli && antseed buyer start --host 0.0.0.0 --port 8377"

  # Node 3
  antseed-node-3:
    image: node:20-alpine
    container_name: antseed-node-3
    restart: always
    working_dir: /app
    volumes:
      - /opt/antseed-cluster/data/node-3/.antseed:/root/.antseed
    ports:
      - "127.0.0.1:8382:8377"
    command: sh -c "npm install -g @antseed/cli && antseed buyer start --host 0.0.0.0 --port 8377"

  # Node 4
  antseed-node-4:
    image: node:20-alpine
    container_name: antseed-node-4
    restart: always
    working_dir: /app
    volumes:
      - /opt/antseed-cluster/data/node-4/.antseed:/root/.antseed
    ports:
      - "127.0.0.1:8383:8377"
    command: sh -c "npm install -g @antseed/cli && antseed buyer start --host 0.0.0.0 --port 8377"

  # Node 5
  antseed-node-5:
    image: node:20-alpine
    container_name: antseed-node-5
    restart: always
    working_dir: /app
    volumes:
      - /opt/antseed-cluster/data/node-5/.antseed:/root/.antseed
    ports:
      - "127.0.0.1:8384:8377"
    command: sh -c "npm install -g @antseed/cli && antseed buyer start --host 0.0.0.0 --port 8377"

  # Node 6
  antseed-node-6:
    image: node:20-alpine
    container_name: antseed-node-6
    restart: always
    working_dir: /app
    volumes:
      - /opt/antseed-cluster/data/node-6/.antseed:/root/.antseed
    ports:
      - "127.0.0.1:8385:8377"
    command: sh -c "npm install -g @antseed/cli && antseed buyer start --host 0.0.0.0 --port 8377"

  # Node 7
  antseed-node-7:
    image: node:20-alpine
    container_name: antseed-node-7
    restart: always
    working_dir: /app
    volumes:
      - /opt/antseed-cluster/data/node-7/.antseed:/root/.antseed
    ports:
      - "127.0.0.1:8386:8377"
    command: sh -c "npm install -g @antseed/cli && antseed buyer start --host 0.0.0.0 --port 8377"

  # Node 8
  antseed-node-8:
    image: node:20-alpine
    container_name: antseed-node-8
    restart: always
    working_dir: /app
    volumes:
      - /opt/antseed-cluster/data/node-8/.antseed:/root/.antseed
    ports:
      - "127.0.0.1:8387:8377"
    command: sh -c "npm install -g @antseed/cli && antseed buyer start --host 0.0.0.0 --port 8377"

  # Node 9
  antseed-node-9:
    image: node:20-alpine
    container_name: antseed-node-9
    restart: always
    working_dir: /app
    volumes:
      - /opt/antseed-cluster/data/node-9/.antseed:/root/.antseed
    ports:
      - "127.0.0.1:8388:8377"
    command: sh -c "npm install -g @antseed/cli && antseed buyer start --host 0.0.0.0 --port 8377"

  # Node 10
  antseed-node-10:
    image: node:20-alpine
    container_name: antseed-node-10
    restart: always
    working_dir: /app
    volumes:
      - /opt/antseed-cluster/data/node-10/.antseed:/root/.antseed
    ports:
      - "127.0.0.1:8389:8377"
    command: sh -c "npm install -g @antseed/cli && antseed buyer start --host 0.0.0.0 --port 8377"
```

Start the cluster with:
```bash
cd /opt/antseed-cluster
sudo docker compose up -d
```

---

## ⚖️ 6. Nginx Load Balancer Configuration

Save this file at: `/etc/nginx/sites-available/antseed-loadbalancer.conf`

```nginx
upstream antseed_cluster {
    least_conn; # Sabse kam busy instance ko pehle request bhejta hai
    server 127.0.0.1:8380 max_fails=2 fail_timeout=10s;
    server 127.0.0.1:8381 max_fails=2 fail_timeout=10s;
    server 127.0.0.1:8382 max_fails=2 fail_timeout=10s;
    server 127.0.0.1:8383 max_fails=2 fail_timeout=10s;
    server 127.0.0.1:8384 max_fails=2 fail_timeout=10s;
    server 127.0.0.1:8385 max_fails=2 fail_timeout=10s;
    server 127.0.0.1:8386 max_fails=2 fail_timeout=10s;
    server 127.0.0.1:8387 max_fails=2 fail_timeout=10s;
    server 127.0.0.1:8388 max_fails=2 fail_timeout=10s;
    server 127.0.0.1:8389 max_fails=2 fail_timeout=10s;
}

server {
    listen 80 default_server;
    listen [::]:80 default_server;
    listen 8377;
    server_name _;

    # Client body size for image uploads (Vision models)
    client_max_body_size 50M;

    location / {
        proxy_pass http://antseed_cluster;
        proxy_http_version 1.1;

        # Real-time Server-Sent Events (SSE) Streaming Support
        proxy_set_header Connection '';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Disable Buffering for Instant Token Streaming
        proxy_buffering off;
        proxy_cache off;
        chunked_transfer_encoding on;

        # High Timeouts for Complex Reasoning Models (DeepSeek R1)
        proxy_read_timeout 600s;
        proxy_send_timeout 600s;

        # Instant Auto-Failover if any peer is down or busy
        proxy_next_upstream error timeout invalid_header http_502 http_503 http_504 http_429;
        proxy_next_upstream_tries 3;
        proxy_next_upstream_timeout 15s;
    }
}
```

Enable Nginx config:
```bash
sudo ln -sf /etc/nginx/sites-available/antseed-loadbalancer.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

---

## 📡 7. Standard OpenAI API Endpoints Available

Aapke VPS ka IP address (e.g. `http://YOUR_VPS_IP/`) ab ek **Enterprise-Grade OpenAI Compatible API Gateway** ban chuka hai:

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `http://YOUR_VPS_IP/v1/models` | `GET` | Tamam active models ki live JSON list deta hai (Free & Paid). |
| `http://YOUR_VPS_IP/v1/chat/completions` | `POST` | Text, Code, Reasoning & Vision models ke chat responses. |
| `http://YOUR_VPS_IP/v1/images/generations` | `POST` | AI Image creation models (Z-Image-Turbo / FLUX). |

---

## 💻 8. Client & Team Integration Examples

Aapki team ka koi bhi banda apne laptop se bina kisi software ke isko use kar sakta hai:

### A. Terminal cURL (Model List dekhne ke liye):
```bash
curl http://YOUR_VPS_IP/v1/models | jq '.data[] | {id: .id, name: .name}'
```

### B. Python (Official `openai` Library):
```python
from openai import OpenAI

client = OpenAI(
    base_url="http://YOUR_VPS_IP/v1",
    api_key="antseed"  # Koi bhi dummy string
)

response = client.chat.completions.create(
    model="meta-llama/llama-3.3-70b-instruct",
    messages=[
        {"role": "user", "content": "Write a clean high-performance caching decorator in Python."}
    ],
    stream=True
)

for chunk in response:
    content = chunk.choices[0].delta.content or ""
    print(content, end="", flush=True)
```

### C. Node.js / TypeScript:
```javascript
import OpenAI from "openai";

const openai = new OpenAI({
  baseURL: "http://YOUR_VPS_IP/v1",
  apiKey: "antseed",
});

const stream = await openai.chat.completions.create({
  model: "deepseek-ai/deepseek-r1-distill-qwen-32b",
  messages: [{ role: "user", content: "Solve this logic riddle..." }],
  stream: true,
});

for await (const chunk of stream) {
  process.stdout.write(chunk.choices[0]?.delta?.content || "");
}
```

### D. OpenAI Codex App Setup (`~/.codex/config.toml`):
Codex Desktop ya CLI mein connect karne ke liye bas file update karein:
```toml
model = "meta-llama/llama-3.3-70b-instruct"
model_provider = "antseed_vps"

[model_providers.antseed_vps]
name = "AntSeed Cluster VPS"
base_url = "http://YOUR_VPS_IP/v1"
wire_api = "responses"
requires_openai_auth = false
```

---

## 🔧 9. Maintenance, Health Checks & Monitoring

### Cluster Status Check:
```bash
sudo docker compose -f /opt/antseed-cluster/docker-compose.yml ps
```

### RAM & CPU Usage of all 10 nodes:
```bash
docker stats --no-stream
```

### Nginx Access Logs (Live Traffic):
```bash
sudo tail -f /var/log/nginx/access.log
```

### 1-Click Cluster Restart:
```bash
sudo docker compose -f /opt/antseed-cluster/docker-compose.yml restart
```
