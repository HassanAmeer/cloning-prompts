# Laya / Jev AI — Complete VPS Master Clone, Model Setup & Design System Guide (A to Z)

> **Document Type:** Full-Stack Standalone VPS Installation & AI Model Deployment Blueprint  
> **Platform Target:** Ubuntu 20.04 / 22.04 / 24.04 LTS (x86_64 or ARM64 VPS)  
> **Theme Official Name:** **Obsidian Noir (Developer Console UI)**  
> **AI Architecture:** System 1 Non-Autoregressive Decision Engine (ModernBERT / English & Multilingual Router)  
> **Backend Stack:** FastAPI + Uvicorn + SQLite + PM2 + Python 3.10+  
> **Live VPS Reference:** `http://187.52.117.2:8000` (Process: `laya-ai`)

---

## 📋 Table of Contents
1. [One-Prompt VPS Master Clone Directive](#1-one-prompt-vps-master-clone-directive)
2. [AI Decision Model Architecture & Setup](#2-ai-decision-model-architecture--setup)
3. [Obsidian Noir Design System (Frontend A-to-Z)](#3-obsidian-noir-design-system-frontend-a-to-z)
4. [A-to-Z Routes & Pages Architecture](#4-a-to-z-routes--pages-architecture)
5. [Database Schema (`db.py` & SQLite)](#5-database-schema-dbpy--sqlite)
6. [Complete REST API Endpoints Specification](#6-complete-rest-api-endpoints-specification)
7. [Step-by-Step VPS Deployment Manual](#7-step-by-step-vps-deployment-manual)

---

## 1. One-Prompt VPS Master Clone Directive

Agar aap kisi bhi naye VPS (DigitalOcean, Hetzner, AWS EC2, Linode, Contabo waghera) par yeh pura AI system ek hi prompt me setup karwana chahte hain, toh yeh prompt copy kar ke run karein:

```
### MASTER PROMPT: STANDALONE VPS CLONE FOR LAYA/JEV AI FULL STACK

Please build, deploy, and verify the complete "Laya / Jev AI" decision engine platform on this Ubuntu VPS from scratch adhering strictly to the following specification:

1. ARCHITECTURE & TECH STACK:
   - Operating System: Ubuntu 22.04 / 24.04 LTS.
   - Backend: Python 3.10+ FastAPI running via Uvicorn on 0.0.0.0:8000 managed by PM2 (laya-ai).
   - Database: SQLite (laya.db via db.py) storing users, API keys, usage hits, sessions, settings, and early access contributors.
   - Frontend: Pure HTML5 + Vanilla CSS3 + Modern JavaScript (no build step, no npm build, no node runtime needed for frontend).
   - Process & Proxy: PM2 for process monitoring + Nginx reverse proxy with SSL support.

2. AI DECISION ENGINE SETUP:
   - Model Archetype: System 1 non-autoregressive decision model (ModernBERT backbone, HuggingFace repo devbeast/laya / devbeast/jev).
   - Functionality: Evaluates state context against boolean (noul), choice, and score questions in a single forward pass without text generation hallucinations.
   - Endpoints: Provide /query endpoint with full JSON verdict summary, confidence score, token usage tracking, and API hit counter incrementation.

3. DESIGN SYSTEM ("Obsidian Noir"):
   - Theme: Deep matte obsidian black (#000000 / #030303 / #080808), sharp borders (#222222), monospace typography (JetBrains Mono).
   - Left-Side Signature Accent: Fixed vertical animated SVG zigzag line at exactly 13% opacity (#ff2a3d) on the far-left edge. Zero other red visual noise.
   - Right-Side Hero Visual: Clean transparent ASCII Art AI Robot (robot.png) with smooth 6s floating micro-animation (@keyframes robotFloat).

4. COMPLETE PAGES & TABS:
   - Landing Page (landing.html / index.html): Interactive decision sandbox, early access contributor signup, metrics grid.
   - Dedicated User Login (login.html): Strictly user authentication. Admin login tab is completely removed from this page.
   - User Dashboard (dashboard.html):
     * Tab 01 (Overview & Hits): Masked API key with reveal/copy and live hits counter.
     * Tab 02 (Decision Playground): Live interactive inference runner against /query with verdict badge, probability %, and formatted JSON inspector.
     * Tab 03 (Code Integration): Pre-filled zero-delay snippets for cURL, Python, Node.js, PHP, and Go with user live API key dynamically inserted.
     * Tab 04 (API Docs) & Tab 05 (System Specs).
   - Admin Portal (admin.html): Protected control room with system telemetry (CPU, RAM, PM2 uptime), API Base URL controller, user deletion (POST /delete-user), contributors list with search/pagination, live PM2 logs, and a dedicated "Attached VPS APIs" reference tab.
   - Clean Routes: Both direct URLs (/, /dashboard.html, /login.html, /admin.html) and dual-brand prefixes (/laya/..., /jev/...). Static routes for /robot.png and /spiral.png that work from any subpath.

5. CRITICAL FRONTEND BUG TO AVOID:
   - DO NOT write literal "<?php" inside JavaScript string literals in any .html file.
   - Shared hosting servers (Apache/LiteSpeed) parse .html files through PHP.
   - Use string split: "<" + "?php" instead. This renders identically but prevents PHP from breaking the JS.
   - This bug causes: blank Code Snippets tab, unclickable buttons, broken event listeners.
```

---

## 2. AI Decision Model Architecture & Setup

### Model Specifications (ModernBERT System 1)
Laya / Jev AI ek **System 1 non-autoregressive decision engine** hai jo text generation ke bajaye classification, scoring aur probability distribution calculate karta hai:
- **Architecture**: ModernBERT-based bidirectional encoder with specialized decision heads.
- **Latency**: Sub-50ms (average 12ms–25ms on CPU; < 5ms on GPU).
- **Supported Question Types**:
  1. `noul`: Boolean decision (`Yes / True` vs `No / False`) with probability percentage.
  2. `choice`: Multi-class probability distribution over candidate categories.
  3. `score`: Normalized numerical score (0.0 to 1.0 or 0 to 100).

### Hugging Face Model Weights & Repositories
- **Primary Repo**: `devbeast/laya` (https://huggingface.co/devbeast/laya)
- **Dual Brand Repo**: `devbeast/jev` (https://huggingface.co/devbeast/jev)

### Inference Pipeline & High-Performance Fallback
Agar server par GPU ya heavy PyTorch weights available na hon, toh `app.py` me built-in intelligent heuristic + neural simulation forward pass engine mojod hai jo 100% realistic token calculation, language routing (`English`, `Latin`, `Multilingual`), confidence scoring, aur decision matrices generate karta hai:

```python
# app.py decision forward pass logic
@app.post("/query")
@app.post("/laya/query")
@app.post("/jev/query")
async def api_query(request: Request, body: QueryRequest):
    api_key = extract_api_key(request)
    # 1. Authenticate API Key or active user session
    user = db.get_user_by_api_key(api_key)
    if not user:
        raise HTTPException(status_code=401, detail="Unauthorized: Invalid API Key")

    # 2. Increment API usage hits
    db.increment_hits(api_key)
    hits = db.get_key_hits(api_key)

    # 3. Non-autoregressive forward pass evaluation
    # Returns structured JSON with verdict, probability, confidence, and tokens
    ...
```

---

## 3. Obsidian Noir Design System (Frontend A-to-Z)

### Color Tokens & Palette
All pages utilize standardized CSS variables:
```css
:root {
    --bg: #000000;
    --bg-body: #030303;
    --bg-card: #080808;
    --bg-card-subtle: #0d0d0d;
    --bg-code: #0a0a0a;
    --text-main: #ffffff;
    --text-muted: #888888;
    --text-faint: #555555;
    --text-code: #e5e5e5;
    --border: #222222;
    --border-light: #333333;
    --border-focus: #ffffff;
    --accent-red: #ff2a3d;
    --accent-red-glow: rgba(255, 42, 61, 0.4);
    --status-online: #10b981;
    --status-warning: #f59e0b;
    --status-error: #ef4444;
    --font-mono: 'JetBrains Mono', 'Fira Code', Menlo, Monaco, Consolas, monospace;
}
```

### JetBrains Mono Monospace Typography
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700&display=swap" rel="stylesheet">
```

### Signature Left-Side Zigzag Pulse Animation (13% Opacity)
```css
.zz-layer-left {
    position: fixed;
    top: 0; bottom: 0; left: 0;
    width: 14px;
    pointer-events: none;
    z-index: 9999;
    overflow: hidden;
    opacity: 0.13;
}
.zz-svg {
    position: absolute;
    top: -300px; bottom: -300px;
    width: 100%; height: calc(100% + 600px);
    animation: zzRun 2.2s linear infinite;
}
.zz-path {
    fill: none;
    stroke: #ff2a3d;
    stroke-width: 2.2px;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 0 4px #ff2a3d);
}
@keyframes zzRun {
    from { transform: translateY(0); }
    to   { transform: translateY(-300px); }
}
```

### Right-Side Transparent AI Robot Hero Asset (`robot.png`)
```css
.spiral-frame {
    max-width: 360px;
    width: 100%;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.spiral-img {
    max-width: 100%;
    height: auto;
    user-select: none;
    pointer-events: none;
    animation: robotFloat 6s ease-in-out infinite;
    filter: drop-shadow(0 15px 35px rgba(255, 255, 255, 0.07));
    transition: filter 0.3s ease;
}
@keyframes robotFloat {
    0%, 100% {
        transform: translateY(0px) scale(1);
        filter: drop-shadow(0 12px 25px rgba(255, 255, 255, 0.05));
    }
    50% {
        transform: translateY(-8px) scale(1.01);
        filter: drop-shadow(0 20px 40px rgba(255, 255, 255, 0.12));
    }
}
```

---

## 4. A-to-Z Routes & Pages Architecture

FastAPI backend (`app.py`) provides direct clean routes alongside subpaths:

```
/root/laya/
├── app.py                      # FastAPI server (all routes, auth, query evaluator)
├── db.py                       # SQLite database manager
├── laya.db                     # SQLite active database
├── landing.html                # Public landing page
├── index.html                  # Sync of landing page
├── dashboard.html              # Authenticated user dashboard (5 tabs)
├── login.html                  # User-only authentication page
├── admin.html                  # Protected admin console with Attached APIs tab
├── test.html                   # Model decision test playground
├── api_page.html               # Public API explorer
├── robot.png                   # Transparent ASCII AI Robot asset
├── spiral.png                  # Asset fallback
├── spiral_crop.png             # Asset fallback
├── config.js                   # Client config
└── api_proxy.php               # PHP reverse proxy (not used on VPS directly)
```

### Public Landing Page (`landing.html` / `index.html`)
- **Routes**: `/`, `/index.html`, `/landing.html`, `/laya`, `/jev`, `/docs`
- **Features**: Dual-brand breadcrumb (`devbeast / jev`), Hero CTA buttons (`start building`, `test model`, `docs`), interactive live decision sandbox, early access contributor registration, metrics grid, and documentation table.

### Dedicated User Login (`login.html`)
- **Routes**: `/login`, `/login.html`, `/laya/login`, `/jev/login`
- **Features**: Clean login & registration forms for regular users. Admin login tab is completely removed from this UI.

### Interactive User Dashboard (`dashboard.html`)
- **Routes**: `/dashboard`, `/dashboard.html`, `/laya/dashboard`, `/jev/dashboard`
- **Tab 01 (Overview & Hits)**: Active API Key with reveal/hide button, copy button, and live hit counter.
- **Tab 02 (Decision Playground)**: Context input, question input, live `executing...` state, verdict badges, probability percentage, and JSON response tree.
- **Tab 03 (Code Integration)**: Pre-filled `<pre id="code_snippet_output">` (zero blank delay), multi-language tabs (cURL, Python, Node.js, PHP, Go), dynamic user API key injection. `min-height: 120px` ensures box is always visible.
- **Tab 04 & 05**: Integrated documentation and ModernBERT engine specifications.

### Admin Control Room (`admin.html`) & Attached APIs Tab
- **Routes**: `/admin`, `/admin.html`, `/adm`, `/laya/adm`, `/jev/adm`
- **Auth**: Username `dev` + Dynamic day of the month password (e.g. `27`) or static configuration.
- **Features**:
  1. Live Server Telemetry (CPU%, RAM, PM2 uptime, disk space).
  2. Brand Switcher & Global API Base URL controller (`POST /admin/set-api-url`).
  3. User Accounts Table with permanent user deletion (`POST /delete-user`).
  4. Free Contributors early access table with search, pagination, delete, and JSON export.
  5. Live PM2 log viewer and log flusher.
  6. **Attached VPS APIs Quick Reference Tab (`tab-attached-apis`)**: Comprehensive table of all active endpoints attached to the VPS.

---

## 5. Database Schema (`db.py` & SQLite)

SQLite schema automatically initialized in `/root/laya/laya.db`:

```sql
-- 1. API Keys & Users
CREATE TABLE IF NOT EXISTS api_keys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    api_key TEXT UNIQUE NOT NULL,
    hits INTEGER DEFAULT 0,
    created_at TEXT NOT NULL,
    last_used_at TEXT,
    password_hash TEXT
);

-- 2. User Authentication Sessions
CREATE TABLE IF NOT EXISTS user_sessions (
    token TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    email TEXT NOT NULL,
    created_at TEXT NOT NULL
);

-- 3. Admin Authentication Sessions
CREATE TABLE IF NOT EXISTS admin_sessions (
    token TEXT PRIMARY KEY,
    created_at TEXT NOT NULL
);

-- 4. Global Settings (Brand, Base URL, Context Window)
CREATE TABLE IF NOT EXISTS system_settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

-- 5. Free Early Access Contributors
CREATE TABLE IF NOT EXISTS contributors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    created_at TEXT NOT NULL,
    status TEXT DEFAULT 'active'
);
```

---

## 6. Complete REST API Endpoints Specification

| Endpoint | Method | Auth Required | Purpose |
| :--- | :--- | :--- | :--- |
| `/query` | `POST` | `X-API-Key` or Bearer Token | Core decision forward pass inference |
| `/user/signup` | `POST` | None | Register user, issue API key (`sk_live_...`), start session |
| `/user/login` | `POST` | None | User authentication & session cookie issuance |
| `/user/logout` | `GET` | Session Cookie | Logout & invalidate session |
| `/user/me` | `GET` | Session Cookie / Bearer | Fetch profile, active key, and real-time usage hits |
| `/user/api-key/rotate` | `POST` | Session Cookie / Bearer | Rotate/regenerate user API key |
| `/admin/login` | `POST` | Admin Credentials | Admin session creation |
| `/admin/metrics` | `GET` | Admin Session | Live CPU%, RAM, PM2 uptime |
| `/admin/logs` | `GET` | Admin Session | Live streaming PM2 server logs |
| `/admin/flush-logs` | `POST` | Admin Session | Flush server log files |
| `/admin/set-api-url` | `POST` | Admin Session | Change global API Base URL across documentation |
| `/delete-user` | `POST` | Admin Session | Permanently delete user and revoke key |
| `/contributor` | `POST` | None | Register community email for free early access |
| `/contributors` | `GET` | Admin Session | List all contributors with pagination |
| `/settings` | `GET` | None | Fetch brand name, context window, api_base_url |
| `/health` | `GET` | None | Uptime check returning JSON `{ "status": "ok" }` |
| `/robot.png` | `GET` | None | Serve transparent AI Robot visual asset |
| `/spiral.png` | `GET` | None | Serve brand visual fallback asset |

---

## 7. Step-by-Step VPS Deployment Manual

### Step 1: System Packages & Node.js 20 / PM2
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y python3 python3-pip python3-venv git curl wget nginx ufw

# Install Node.js & PM2
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
sudo npm install -g pm2
```

### Step 2: Project Directory & Permissions
```bash
mkdir -p /root/laya && cd /root/laya
# Place all files (app.py, db.py, *.html, robot.png, etc.) inside /root/laya
chmod -R 755 /root/laya
```

### Step 3: Python Environment & AI Packages
```bash
cd /root/laya
python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install fastapi uvicorn pydantic requests
```

### Step 4: Database Initialization
```bash
python3 -c "import db; db.init_db(); print('SQLite Database successfully initialized!')"
```

### Step 5: PM2 Process Management (`laya-ai`)
```bash
cd /root/laya
pm2 start "/root/laya/venv/bin/uvicorn app:app --host 0.0.0.0 --port 8000" --name "laya-ai"
pm2 save
pm2 startup
```

### Step 6: Nginx Reverse Proxy & Free SSL
Create `/etc/nginx/sites-available/laya`:
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```
Enable & SSL:
```bash
sudo ln -s /etc/nginx/sites-available/laya /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl restart nginx
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Step 7: Verification & Health Checks
```bash
# 1. Health Ping
curl -I http://localhost:8000/
# Expected: 200 OK

# 2. Decision Query Evaluation
curl -X POST http://localhost:8000/query \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_live_demo" \
  -d '{"text": "Chargeback filed on credit card", "question": "Is this a fraud risk?", "type": "noul"}'
# Expected: JSON response with verdict badge and probability score

# 3. Settings endpoint
curl http://localhost:8000/settings
# Expected: JSON with brand_name, context_window, api_base_url
```
