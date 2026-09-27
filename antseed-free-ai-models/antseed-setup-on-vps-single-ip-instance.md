# 🐜 Antseed AI — VPS Single-Instance Setup Blueprint

> **File:** `antseed-setup-on-vps-single-instance.md`  
> **Target Environment:** Standard Ubuntu VPS (1-2 vCPU, 2-4 GB RAM, Single Dedicated Public IP)  
> **Architecture:** Single High-Efficiency Antseed Node + Nginx Reverse Proxy & Streaming Gateway  
> **Capacity:** 15–30 Concurrent User Requests / 100–300 Registered Team Users  

---

## 📌 Table of Contents (Fehrist)
1. [Overview & Single-Instance Use Cases](#-1-overview--use-cases)
2. [Capacity & Resource Footprint](#-2-capacity--resource-footprint)
3. [🤖 Master Agent Prompt (VPS Par 1-Click Setup Ke Liye)](#-3-master-agent-prompt-copy--paste)
4. [🛠️ Manual Step-by-Step Installation (Systemd Service)](#-4-manual-step-by-step-installation)
5. [🌐 Nginx Reverse Proxy Configuration (Port 80 -> 8377)](#-5-nginx-reverse-proxy-configuration)
6. [📡 Standard OpenAI API Endpoints Available](#-6-standard-openai-api-endpoints-available)
7. [💻 Client & Team Integration (cURL, Python, Node.js, Codex)](#-7-client--team-integration-examples)
8. [🔧 Service Management & Commands](#-8-service-management--commands)

---

## 🎯 1. Overview & Use Cases

Agar aapke paas ek doosra VPS hai jisme **sirf 1 Public IP** hai, toh yeh setup us VPS ke liye sabse lightweight, simple aur fast tareeqa hai.

### Best For:
- **Personal Development:** Codex, Cursor, VS Code, ya terminal coding assistants.
- **Small Team:** 10 se 25 bando ki team jo rozana testing aur coding karti hai.
- **Microservices / Websites:** Aapki company ki websites ya custom chatbots jo background mein AI prompts bhejte hain.

---

## 📊 2. Capacity & Resource Footprint

| Parameter | Value | Details |
| :--- | :--- | :--- |
| **RAM Usage** | **~100 MB to 150 MB** | Intehayi halka-phulka! 1GB RAM wale VPS par bhi chal jata hai |
| **CPU Usage** | **< 2%** | Sirf HTTP JSON stream forwarding (0% GPU compute) |
| **Storage Usage** | **~150 MB** | Minimal disk footprint |
| **Concurrent Capacity** | **15 to 30 Active Streams** | Ek hi second mein 30 users bina kisi rukawat ke jawab le sakte hain |
| **Total Team Capacity** | **100 to 300 Users** | Standard development workflow ke mutabiq |

---

## 🤖 3. Master Agent Prompt (Copy & Paste)

> **Jab aap doosre VPS par kisi AI Coding Assistant (Antigravity, Cursor, Codex, Claude Code) ko run karein, toh yeh prompt copy karke paste kar dein:**

```markdown
You are an expert Linux Systems Administrator. Your task is to set up a permanent, production-ready Single-Instance Antseed AI Node with an Nginx reverse proxy on this Ubuntu VPS.

Requirements & Tasks:
1. Environment Setup:
   - Ensure Node.js (v18+) and npm are installed.
   - Install @antseed/cli globally (`npm install -g @antseed/cli`).
2. Antseed Configuration:
   - Configure /root/.antseed/config.json with:
     {
       "buyer": {
         "routingPreferences": {
           "preferFreePeers": true,
           "maxInputUsdPerMillion": 25,
           "minTrustScore": 0
         }
       }
     }
3. Background Service (Systemd):
   - Create a systemd service at /etc/systemd/system/antseed.service that executes `antseed buyer start --host 127.0.0.1 --port 8377` with Restart=always.
   - Enable and start antseed.service.
4. Nginx Reverse Proxy:
   - Configure Nginx on Port 80 (and Port 8377) to proxy to http://127.0.0.1:8377.
   - Set proxy_buffering off; chunked_transfer_encoding on; for real-time Server-Sent Events (SSE) AI token streaming.
   - Increase proxy_read_timeout to 600s for deep-reasoning models.
5. Firewall & Verification:
   - Open ports 80, 443, 8377, and SSH in UFW.
   - Test `curl http://127.0.0.1/v1/models` and confirm an OpenAI-compatible JSON list of 270+ models is returned.
```

---

## 🛠️ 4. Manual Step-by-Step Installation

### Step 4.1: Packages & Node.js 20 Setup
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git ufw jq nginx

# Node.js 20 LTS Install karein
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Antseed CLI Install karein
sudo npm install -g @antseed/cli
```

### Step 4.2: Antseed Configuration
```bash
sudo mkdir -p /root/.antseed
cat << 'EOF' | sudo tee /root/.antseed/config.json > /dev/null
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
```

### Step 4.3: Permanent Systemd Background Service
Save this file at: `/etc/systemd/system/antseed.service`

```ini
[Unit]
Description=Antseed P2P Buyer AI Service
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/root
ExecStart=/usr/bin/antseed buyer start --host 127.0.0.1 --port 8377
Restart=always
RestartSec=5
Environment=NODE_ENV=production

# Security & Limits
LimitNOFILE=65535

[Install]
WantedBy=multi-user.target
```

Enable & Start Service:
```bash
sudo systemctl daemon-reload
sudo systemctl enable --now antseed
sudo systemctl status antseed --no-pager
```

---

## 🌐 5. Nginx Reverse Proxy Configuration

Save this file at: `/etc/nginx/sites-available/antseed.conf`

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    listen 8377;
    server_name _;

    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:8377;
        proxy_http_version 1.1;

        # Real-time SSE Token Streaming
        proxy_set_header Connection '';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Disable Buffering for Instant Typing Effect
        proxy_buffering off;
        proxy_cache off;
        chunked_transfer_encoding on;

        # High timeout for complex coding & reasoning
        proxy_read_timeout 600s;
        proxy_send_timeout 600s;
    }
}
```

Enable & Reload Nginx:
```bash
sudo ln -sf /etc/nginx/sites-available/antseed.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# Firewall Ports
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 8377/tcp
sudo ufw --force enable
```

---

## 📡 6. Standard OpenAI API Endpoints Available

Aapke VPS ki Public IP par tamam OpenAI routes live ho chuke hain:

### 1. Model Explorer (`GET /v1/models`):
VPS par active tamam models (Llama 3.3, DeepSeek R1, Qwen 2.5, GLM 5.1 وغیرہ) ki list JSON format mein milti hai:
```bash
curl http://YOUR_VPS_IP/v1/models
```

### 2. Chat & Reasoning (`POST /v1/chat/completions`):
Standard OpenAI completion request. Streaming enabled hone par tokens real-time screen par aate hain.

### 3. Image Generation (`POST /v1/images/generations`):
AI art models (Z-Image-Turbo) ke zariye text prompt se images generate karne ke liye.

---

## 💻 7. Client & Team Integration Examples

### A. Terminal cURL (Fast Test):
```bash
curl http://YOUR_VPS_IP/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer antseed" \
  -d '{
    "model": "meta-llama/llama-3.3-70b-instruct",
    "messages": [
      {"role": "user", "content": "Explain Docker in two short sentences."}
    ]
  }'
```

### B. Python (`openai` Library):
```python
from openai import OpenAI

client = OpenAI(
    base_url="http://YOUR_VPS_IP/v1",
    api_key="antseed"
)

response = client.chat.completions.create(
    model="meta-llama/llama-3.3-70b-instruct",
    messages=[{"role": "user", "content": "Write a FastAPI CRUD boilerplate."}],
    stream=True
)

for chunk in response:
    print(chunk.choices[0].delta.content or "", end="", flush=True)
```

### C. Node.js:
```javascript
import OpenAI from "openai";

const openai = new OpenAI({
  baseURL: "http://YOUR_VPS_IP/v1",
  apiKey: "antseed",
});

const completion = await openai.chat.completions.create({
  model: "deepseek-ai/deepseek-r1-distill-qwen-32b",
  messages: [{ role: "user", content: "What is 17 * 19?" }],
});

console.log(completion.choices[0].message.content);
```

### D. OpenAI Codex Desktop App (`~/.codex/config.toml`):
```toml
model = "meta-llama/llama-3.3-70b-instruct"
model_provider = "antseed_single"

[model_providers.antseed_single]
name = "AntSeed Single VPS"
base_url = "http://YOUR_VPS_IP/v1"
wire_api = "responses"
requires_openai_auth = false
```

---

## 🔧 8. Service Management & Commands

| Task | Command |
| :--- | :--- |
| **Service Status** | `sudo systemctl status antseed` |
| **Live Logs Check** | `sudo journalctl -u antseed -f` |
| **Restart Node** | `sudo systemctl restart antseed` |
| **Stop Node** | `sudo systemctl stop antseed` |
| **Nginx Reload** | `sudo systemctl reload nginx` |
