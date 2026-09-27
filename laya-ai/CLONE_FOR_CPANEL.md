# Laya / Jev AI — Complete cPanel & Hostinger hPanel Clone Guide (API Backend Connected)

> **Document Type:** Static & PHP Reverse Proxy Client Deployment Guide for Shared Hosting  
> **Target Platforms:** Hostinger hPanel, Traditional cPanel, Plesk, Apache, LiteSpeed  
> **Backend AI Engine:** Remote VPS Backend (`http://187.52.117.2:8000` or custom VPS domain)  
> **Theme Official Name:** **Obsidian Noir (Developer Console UI)**  
> **Frontend Stack:** Pure HTML5 + CSS3 + Modern Vanilla JS + PHP Proxy (Zero Node/Build Tools Required)

---

## 📋 Table of Contents
1. [One-Prompt cPanel/Hostinger Master Clone Directive](#1-one-prompt-cpanelhostinger-master-clone-directive)
2. [Architecture: How Shared Hosting Connects to VPS via API](#2-architecture-how-shared-hosting-connects-to-vps-via-api)
3. [Obsidian Noir Design System on Shared Hosting](#3-obsidian-noir-design-system-on-shared-hosting)
4. [File Structure for `public_html/`](#4-file-structure-for-public_html)
5. [Step-by-Step Deployment Guide](#5-step-by-step-deployment-guide)
6. [API Configuration (`config.js`) & Reverse Proxy (`api_proxy.php`)](#6-api-configuration-configjs--reverse-proxy-api_proxyphp)
7. [Apache & LiteSpeed `.htaccess` Configuration](#7-apache--litespeed-htaccess-configuration)
8. [CRITICAL: PHP Tag Bug on Shared Hosting (MUST READ)](#8-critical-php-tag-bug-on-shared-hosting-must-read)
9. [A-to-Z Features on cPanel](#9-a-to-z-features-on-cpanel)
10. [Pre-Flight Verification Checklist](#10-pre-flight-verification-checklist)

---

## 1. One-Prompt cPanel/Hostinger Master Clone Directive

Agar aap kisi bhi AI coding assistant ko Hostinger ya cPanel par pura frontend clone deploy karne ka prompt dena chahte hain:

```
### MASTER PROMPT: CPANEL / HOSTINGER HPANEL CLONE FOR LAYA/JEV AI

Please deploy the complete client-side clone of "Laya / Jev AI" on this cPanel / Hostinger hPanel shared hosting environment adhering strictly to the following specification:

1. ARCHITECTURE & CONNECTION:
   - Frontend runs entirely on static HTML5 + Vanilla CSS3 + Modern JavaScript + PHP.
   - All dynamic actions (Decision Model Query, User Login, Signup, Session Profile, Admin Telemetry, PM2 Logs) communicate with the remote VPS backend (http://YOUR_VPS_IP:8000) via config.js and api_proxy.php.
   - Zero Node.js build step, zero npm run build. Files are directly extractable in public_html/.

2. THEME & VISUAL IDENTITY ("Obsidian Noir"):
   - Pure black/obsidian palette (#000000 / #030303 / #080808), sharp borders (#222222), monospace typography (JetBrains Mono).
   - Signature Left-Side Accent: Fixed SVG vertical zigzag pulse line strictly on the far-left edge at exactly 13% opacity (#ff2a3d).
   - Right-Side Hero Visual: Clean transparent ASCII Art AI Robot (robot.png) with smooth floating keyframe micro-animation (robotFloat).

3. CLIENT PAGES & FEATURES:
   - index.html: Landing page with interactive live decision sandbox, early access contributor registration, metrics grid, and documentation.
   - login.html: Dedicated user login & registration. Admin login tab is completely separated and removed from this page.
   - dashboard.html: User developer console with 5 tabs:
     * Tab 01 (Overview): Masked API key with reveal/copy and live hits counter.
     * Tab 02 (Decision Playground): Live interactive inference runner against VPS /query with verdict badge, probability %, and formatted JSON inspector.
     * Tab 03 (Code Integration): Pre-filled zero-flicker snippets for cURL, Python, Node.js, PHP, and Go with user live API key dynamically inserted.
     * Tab 04 & 05: API Docs and System Specs.
   - admin.html: Protected admin control center with live system telemetry, API Base URL customizer, user deletion, early access contributors list with pagination/search, live server logs, and a dedicated "Attached VPS APIs" reference tab.
   - test.html & api.html: Standalone model test sandbox and API explorer.

4. SERVER FILES:
   - api_proxy.php: High-performance PHP curl proxy to route requests to VPS backend without CORS or Mixed Content errors.
   - config.js: Central configuration. Sets window.LAYA_CONFIG.API_BASE_URL and auto-enables USE_PROXY on live domains.
   - .htaccess: Clean URL rewrites (omits .html), HTTPS redirection, Gzip compression, and security headers.

5. CRITICAL BUG TO AVOID (SHARED HOSTING PHP PARSING):
   - Apache/LiteSpeed on cPanel/Hostinger often parses .html files through PHP.
   - NEVER write literal "<?php" inside JavaScript string literals in .html files.
   - USE INSTEAD: "<" + "?php" — this renders identically in browser but PHP never sees it as a tag.
   - This bug causes: blank Code Snippets tab, unclickable lang buttons, broken event listeners.
   - All affected files: dashboard.html, index.html, api.html must use the split string approach.
```

---

## 2. Architecture: How Shared Hosting Connects to VPS via API

```
+-------------------------------------------------+
|    Client Browser (User Visiting Domain)        |
|      e.g., https://yourdomain.com/              |
+-------------------+-----------------------------+
                    |
      +-------------+-------------+
      v                           v
+---------------------+  +------------------------------------+
|  Direct Browser     |  |   cPanel PHP Proxy Fallback        |
|  Fetch (CORS mode)  |  |   https://yourdomain.com/          |
|                     |  |   api_proxy.php?endpoint=/query    |
+----------+----------+  +------------------+-----------------+
           |                                | Server-to-Server
           |                                | (Bypasses CORS & SSL)
           +----------------+---------------+
                            v
        +---------------------------------------+
        |           VPS AI Backend              |
        |      http://187.52.117.2:8000         |
        | (FastAPI + SQLite + Decision Engine)  |
        +---------------------------------------+
```

Shared hosting (cPanel/Hostinger) par Python ya PyTorch runtime chalane ki zaroorat nahi hai. Client side static files render hoti hain aur runtime API requests direct VPS par execute hoti hain.

### How `config.js` Auto-Selects Direct vs Proxy Mode
```javascript
// config.js (already in package)
window.LAYA_CONFIG = {
    API_BASE_URL: "http://187.52.117.2:8000",
    // Auto-enabled on live SSL domains; disabled on VPS:8000 and localhost
    USE_PROXY: (window.location.port !== '8000') && (
        (window.location.protocol === 'https:' && !window.location.host.includes('187.52.117.2')) ||
        (!window.location.host.includes('187.52.117.2') &&
         !window.location.host.includes('localhost') &&
         !window.location.host.includes('127.0.0.1'))
    ),
    PROXY_ENDPOINT: 'api_proxy.php'
};
```

---

## 3. Obsidian Noir Design System on Shared Hosting

### Monospace Typography & JetBrains Mono
Har HTML file ke `<head>` me Google Font **JetBrains Mono** include hai:
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

### Right-Side Transparent AI Robot Hero Visual (`robot.png`)
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

## 4. File Structure for `public_html/`

Zip file extract hone ke baad aapke cPanel / Hostinger ke `public_html/` me yeh structure hota hai:

```
public_html/
├── .htaccess               # Clean URLs (removes .html), HTTPS, Gzip compression
├── index.html              # Landing Page with live sandbox & robot image
├── dashboard.html          # User Developer Console (Playground, Code Snippets)
├── login.html              # Dedicated User Login (Admin tab removed)
├── admin.html              # Admin Portal with Attached VPS APIs tab
├── test.html               # Model Decision Testing Console
├── api.html                # Interactive API Explorer
├── config.js               # Central VPS API Address + auto proxy config
├── api_proxy.php           # High-Performance PHP Reverse Proxy
├── sitemap.xml             # Search Engine XML Sitemap
├── robots.txt              # Search Engine Robot Instructions
├── robot.png               # Transparent AI Robot Hero Asset
├── spiral.png              # Asset fallback
├── spiral_crop.png         # Asset fallback
└── CLONE_FOR_CPANEL.md     # This complete manual
```

---

## 5. Step-by-Step Deployment Guide

### Hostinger hPanel File Manager Method
1. **Login to Hostinger**: Apne Hostinger dashboard par ja kar **Websites** -> **Manage** par click karein.
2. **File Manager**: Left menu se **Files** -> **File Manager** kholein.
3. **Navigate to `public_html`**:
   - Agar main domain (`https://yourdomain.com/`) par lagana hai, toh direct `public_html/` folder me jayein.
   - Agar sub-folder (`https://yourdomain.com/laya/`) par lagana hai, toh `public_html/laya/` folder banayein.
4. **Upload Zip**: Top bar me **Upload** icon dabayein aur `laya_hostinger.zip` (ya `laya.zip`) upload karein.
5. **Extract**: Upload hone ke baad zip par right click karke **Extract** select karein.
6. **Done**: Website instantly live ho jayegi!

### Traditional cPanel File Manager Method
1. **Login to cPanel**: Apne hosting provider (Namecheap, GoDaddy, Bluehost, etc.) ke cPanel me login karein.
2. **Open File Manager**: **Files** section me **File Manager** par click karein.
3. **Go to `public_html`**: Document root me jayein.
4. **Upload & Extract**: `laya_hostinger.zip` upload karein aur **Extract** par click karein.
5. **Permissions**: Folders `755`, Files `644`.

---

## 6. API Configuration (`config.js`) & Reverse Proxy (`api_proxy.php`)

### Why PHP Proxy is Essential
Agar aapki cPanel website par SSL certificate laga hai (`https://yourdomain.com`) aur aapka VPS backend HTTP par chal raha hai (`http://187.52.117.2:8000`), toh browser **Mixed Content Error** ki wajah se direct requests block kar deta hai.

Is masle ko hal karne ke liye humne **`api_proxy.php`** provide kiya hai:
- Browser se request `https://yourdomain.com/api_proxy.php?endpoint=/query` par aati hai (Same-Origin HTTPS).
- PHP cURL server-to-server VPS backend ko call karta hai aur instant JSON response return karta hai.
- Zero Mixed Content errors aur zero CORS blocks!

### Editing `config.js` for Your Server
Apne File Manager me `config.js` open karein aur apna VPS IP ya backend URL set karein:

```javascript
// config.js — only this line needs changing for a new VPS
API_BASE_URL: "http://YOUR_VPS_IP:8000",
```

`USE_PROXY` is auto-computed — you don't need to change it manually.

---

## 7. Apache & LiteSpeed `.htaccess` Configuration

Aapke package me `.htaccess` pehle se configured hai jo:
1. File extension `.html` ko URLs se chupa deta hai (`/dashboard` opens `dashboard.html`).
2. Gzip compression enable karta hai.
3. CORS security headers provide karta hai.

```apache
RewriteEngine On
RewriteBase /

# 1. Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# 2. Clean URLs (Remove .html extension)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}.html -f
RewriteRule ^([^\.]+)$ $1.html [NC,L]

# 3. Security & CORS Headers
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS, PUT, DELETE"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-API-Key"
</IfModule>
```

---

## 8. CRITICAL: PHP Tag Bug on Shared Hosting (MUST READ)

### The Problem
Hostinger hPanel, cPanel, and any Apache/LiteSpeed server is often configured with:
```apache
AddHandler application/x-httpd-php .html
```
This means **`.html` files are parsed by PHP**. If any `.html` file contains the literal string `<?php` (even inside a JavaScript string), PHP will interpret everything after it as PHP code — **breaking all JavaScript from that point forward**.

### Symptoms of This Bug
- Code Integration Snippets tab: **completely empty `<pre>` box**
- Language buttons (cURL, Python, Node.js, PHP, Go): **unclickable / not responding**  
- All `addEventListener` calls after the `<?php` string: **never registered**
- The bug only appears on live hosting — works fine locally

### The Fix (Already Applied in This Package)
In all `.html` files (`dashboard.html`, `index.html`, `api.html`), the PHP snippet is split:
```javascript
// WRONG — breaks on shared hosting:
return "<?php\n" + ...

// CORRECT — safe everywhere:
return "<" + "?php\n" + ...
```
The output in the browser is identical (`<?php`), but PHP never sees the opening tag.

### If You Write a New Clone
Always remember: any time you write `<?php` inside a JavaScript string in an HTML file that runs on shared hosting, use `"<" + "?php"` instead.

---

## 9. A-to-Z Features on cPanel

### Landing Page Live Sandbox (`index.html`)
- User sample state context select karta hai aur **"Run Evaluation"** par click karta hai.
- Javascript function `window.authFetch('/query')` VPS backend par query forward karta hai aur instant probability verdict badge render karta hai.

### Dedicated User Login (`login.html`)
- User registration and login forms.
- Admin login tab completely removed so normal visitors cannot access administrative routes.

### User Dashboard Tabs 01 to 05 (`dashboard.html`)
- **Tab 01**: Masked API key display with copy-to-clipboard and live usage hits fetched via `GET /user/me`.
- **Tab 02 (Playground)**: Interactive decision query tester against VPS backend with latency indicator.
- **Tab 03 (Code Integration)**: Zero-flicker pre-rendered snippets (cURL, Python, Node.js, PHP, Go) with dynamic user API key pre-population. `min-height: 120px` on `<pre>` ensures box is never invisible.
- **Tab 04 & 05**: System architecture specs and documentation tables.

### Admin Portal & Attached APIs Tab (`admin.html`)
- Live VPS telemetry (CPU, RAM, uptime).
- User deletion (`POST /delete-user`) directly from cPanel frontend.
- Searchable & paginated community contributors list.
- **Dedicated Attached VPS APIs Quick Reference Tab**: Complete table showing every API endpoint connected to the VPS.

---

## 10. Pre-Flight Verification Checklist
- [ ] Extract `laya_hostinger.zip` into `public_html/`.
- [ ] Edit `config.js`: set `API_BASE_URL` to your VPS IP/domain.
- [ ] Check `https://yourdomain.com/` displays the Obsidian Noir landing page with transparent AI robot.
- [ ] Check far-left zigzag line is animating at 13% opacity.
- [ ] Click **"start building"** or test sandbox on landing page; verify instant response from VPS `/query`.
- [ ] Visit `https://yourdomain.com/login.html` and verify user login (confirm admin tab is absent).
- [ ] Visit `https://yourdomain.com/dashboard.html` — **Code Integration tab must show pre-filled cURL code without blank box**.
- [ ] Click **Python**, **Node.js**, **PHP**, **Go** buttons — all must switch code snippet.
- [ ] Visit `https://yourdomain.com/admin.html` and verify Attached VPS APIs tab is present.
