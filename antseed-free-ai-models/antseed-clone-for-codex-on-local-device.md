# 🐜 Antseed to Codex — Universal Setup & Agent Cloning Blueprint

> **Master Markdown Blueprint (`antseed-clone-to-codex.md`)**  
> *Is document ko kisi bhi new system (Linux, macOS, Windows) mein kisi bhi AI Agent (Antigravity, Cursor, Codex, Claude Code, Aider) ya terminal mein daalein, aur yeh Antseed P2P network, Web Studio Tester aur Codex Desktop ko 100% automate karke clone kar dega!*

---

## 📌 Table of Contents
1. [🤖 Master Agent Prompt (Kisi Bhi AI Agent Ke Liye)](#-1-master-agent-prompt-copy--paste)
2. [⚡ 1-Click Automated Setup Script (Linux & macOS)](#-2-1-click-automated-setup-script-linux--macos)
3. [🪟 Windows Setup Instructions (PowerShell / WSL)](#-3-windows-setup-instructions)
4. [🏗️ Codex Desktop UI & CLI Connection](#-4-codex-desktop-ui--cli-connection)
5. [🎮 System Commands Cheatsheet](#-5-system-commands-cheatsheet)
6. [🌐 Live Free Models & Direct Peer Pinning](#-6-live-free-models--direct-peer-pinning)

---

## 🤖 1. Master Agent Prompt (Copy & Paste)

Agar aap kisi doosre computer par kisi bhi AI Coding Assistant (Antigravity, Cursor, Codex, Claude Code, etc.) ko yeh pura setup clone karne ka bolna chahein, toh sirf neeche diya gaya prompt copy karke us AI ko bhej dein:

```markdown
You are an expert systems and AI integration agent. Your task is to set up a complete, permanent, decentralized Antseed AI environment with an interactive Multi-Modal Web Studio and connect it seamlessly to OpenAI Codex (both Desktop App and CLI).

Please perform the following steps autonomously:
1. Verify Node.js (v18+) and npm are installed.
2. Install the Antseed P2P CLI globally using `npm i -g @antseed/cli` (or in a permanent user path like `~/.local/share/antseed-cli` with a launcher in `~/.local/bin/antseed`).
3. Create the Antseed configuration at `~/.antseed/config.json` with:
   {
     "buyer": {
       "routingPreferences": {
         "preferFreePeers": true,
         "maxInputUsdPerMillion": 25,
         "minTrustScore": 0
       }
     }
   }
4. Scaffold the complete Antseed Web Studio Tester into the user's permanent project directory at `~/antseed-tester/`:
   - `server.js`: Native Node.js HTTP/JSON proxy server listening on port 3000 that forwards to Antseed local proxy on `http://127.0.0.1:8377`.
   - `public/index.html`, `public/styles.css`, `public/app.js`: Interactive Multi-Modal Web Studio supporting 2-level hierarchical categories (All/Free/Paid + Text, Reasoning, Vision, Image, Audio, Code), streaming responses, latency benchmarking, and direct peer pinning.
   - `start.sh`: Launcher that automatically checks port 8377, starts `antseed buyer start` if not already running, and launches `node server.js` on port 3000.
   - `stop.sh`: Clean termination script for both `node server.js` and `antseed buyer start`.
5. Create system-wide executable commands and aliases in `~/.local/bin/` (and export to `~/.bashrc` / `~/.zshrc`):
   - `antseed-start`: Launches Web Studio on port 3000 and auto-starts proxy on port 8377.
   - `antseed-web`: Alias to `antseed-start`.
   - `antseed-proxy`: Starts only the background OpenAI P2P proxy on `http://127.0.0.1:8377/v1`.
   - `antseed-stop`: Cleanly stops all Antseed services.
   - `antseed-models`: CLI tool that queries `http://127.0.0.1:8377/v1/models` and prints a formatted table of all currently live FREE models with their exact `peerId@model` IDs.
   - `codex-model`: Interactive CLI model switcher that lists all live free models (DeepSeek V4, GLM 5.3, Gemma 4, Qwen3, etc.), prompts the user for a number, and instantly updates `~/.codex/config.toml`.
6. Configure OpenAI Codex (Desktop & CLI) in `~/.codex/config.toml`:
   - Set `model_provider = "antseed"`
   - Set default free model: `model = "1d90f467689d499dc435e5744b4613c3203eb0aa@deepseek-v4-flash"`
   - Configure `[model_providers.antseed]` with `base_url = "http://localhost:8377/v1"`, `env_key = "ANTSEED_API_KEY"`, `wire_api = "responses"`, and `requires_openai_auth = false`.
   - Export `ANTSEED_API_KEY="antseed"` and `OPENAI_BASE_URL="http://localhost:8377/v1"` into shell profile.
7. Test the setup: Verify `antseed buyer start` listens on 8377, run a test prompt via curl or codex exec, and confirm the environment is 100% operational.
```

---

## ⚡ 2. 1-Click Automated Setup Script (Linux & macOS)

Naye computer par yeh script chalane se poora setup khud-ba-khud tayyar ho jayega:

```bash
#!/usr/bin/env bash
set -e

echo "🚀 Starting Automated Setup of Antseed AI & Codex Integration..."

USER_HOME="$HOME"
SHELL_RC="$USER_HOME/.bashrc"
if [ "$SHELL" = "*/zsh" ] || [ -f "$USER_HOME/.zshrc" ]; then
  SHELL_RC="$USER_HOME/.zshrc"
fi

BIN_DIR="$USER_HOME/.local/bin"
mkdir -p "$BIN_DIR" "$USER_HOME/.antseed" "$USER_HOME/.codex" "$USER_HOME/antseed-tester"

# 1. Install Antseed CLI
if ! command -v antseed >/dev/null 2>&1; then
  echo "📦 Installing Antseed CLI..."
  npm install -g @antseed/cli || npm install --prefix "$USER_HOME/.local" -g @antseed/cli
fi

# 2. Antseed Config (Enable Free Peers Priority)
echo "⚙️ Configuring Antseed routing preferences..."
cat > "$USER_HOME/.antseed/config.json" << 'JSON_EOF'
{
  "buyer": {
    "routingPreferences": {
      "preferFreePeers": true,
      "maxInputUsdPerMillion": 25,
      "minTrustScore": 0
    }
  }
}
JSON_EOF

# 3. Codex Config (~/.codex/config.toml)
echo "⚙️ Configuring Codex config.toml..."
cat > "$USER_HOME/.codex/config.toml" << 'TOML_EOF'
# Popular Free Models List:
# 1. DeepSeek V4 Flash : 1d90f467689d499dc435e5744b4613c3203eb0aa@deepseek-v4-flash
# 2. GLM 4.7 Flash     : 4668854ba3e8b094e6f48fbeb59cec1cfde162f2@glm-4.7-flash
# 3. Gemma 4 E4b       : 1d90f467689d499dc435e5744b4613c3203eb0aa@gemma-4-e4b
# 4. Agnes 3 Flash     : 0b0b9574d5b038f6fd906325e699020423adf446@agnes-3-flash
# 5. GLM 5.3 Flash     : 1d90f467689d499dc435e5744b4613c3203eb0aa@glm-5.3-flash

model = "1d90f467689d499dc435e5744b4613c3203eb0aa@deepseek-v4-flash"
model_provider = "antseed"

[model_providers.antseed]
name = "Antseed"
base_url = "http://localhost:8377/v1"
env_key = "ANTSEED_API_KEY"
wire_api = "responses"
requires_openai_auth = false
TOML_EOF

# 4. Create Commands
cat > "$BIN_DIR/antseed-start" << 'SCRIPT_EOF'
#!/usr/bin/env bash
cd "$HOME/antseed-tester" && ./start.sh "$@"
SCRIPT_EOF
chmod +x "$BIN_DIR/antseed-start"

cat > "$BIN_DIR/antseed-proxy" << 'SCRIPT_EOF'
#!/usr/bin/env bash
echo "🚀 Starting Antseed P2P Buyer Proxy on :8377..."
exec antseed buyer start "$@"
SCRIPT_EOF
chmod +x "$BIN_DIR/antseed-proxy"

cat > "$BIN_DIR/antseed-stop" << 'SCRIPT_EOF'
#!/usr/bin/env bash
echo "🛑 Stopping Antseed Web Server..."
pkill -f "node server.js" 2>/dev/null || true
echo "🛑 Stopping Antseed Buyer Proxy..."
pkill -f "antseed buyer start" 2>/dev/null || true
pkill -f "node.*antseed.*buyer.*start" 2>/dev/null || true
echo "✅ All Antseed services stopped."
SCRIPT_EOF
chmod +x "$BIN_DIR/antseed-stop"

# 5. Shell Environment
grep -q "ANTSEED_API_KEY" "$SHELL_RC" || cat >> "$SHELL_RC" << 'SH_EOF'

# Antseed Decentralized AI
export PATH="$HOME/.local/bin:$PATH"
export ANTSEED_API_KEY="antseed"
export OPENAI_BASE_URL="http://localhost:8377/v1"
export OPENAI_API_KEY="antseed"

alias antseed-start='$HOME/.local/bin/antseed-start'
alias antseed-web='$HOME/.local/bin/antseed-start'
alias antseed-proxy='$HOME/.local/bin/antseed-proxy'
alias antseed-stop='$HOME/.local/bin/antseed-stop'
alias codex-model='$HOME/.local/bin/codex-model'
alias antseed-models='$HOME/.local/bin/antseed-models'
SH_EOF

echo "🎉 Setup complete! Use 'antseed-start' or 'antseed-proxy'."
```

---

## 🪟 3. Windows Setup Instructions

### Option A: WSL (Windows Subsystem for Linux — Recommended)
1. PowerShell mein chalayein: `wsl --install`
2. Ubuntu terminal khol kar upar di gayi script execute karein.
3. Windows browser mein `http://localhost:3000` chal jayega!

### Option B: Native Windows (PowerShell)
1. Node.js official site (`https://nodejs.org/`) se install karein.
2. Terminal (PowerShell) mein:
   ```powershell
   npm install -g @antseed/cli
   ```
3. Folder `C:\Users\<Username>\.antseed\config.json` banayein:
   ```json
   {
     "buyer": {
       "routingPreferences": {
         "preferFreePeers": true,
         "maxInputUsdPerMillion": 25,
         "minTrustScore": 0
       }
     }
   }
   ```
4. Codex `C:\Users\<Username>\.codex\config.toml` mein update karein:
   ```toml
   model = "1d90f467689d499dc435e5744b4613c3203eb0aa@deepseek-v4-flash"
   model_provider = "antseed"

   [model_providers.antseed]
   name = "Antseed"
   base_url = "http://localhost:8377/v1"
   env_key = "ANTSEED_API_KEY"
   wire_api = "responses"
   requires_openai_auth = false
   ```

---

## 🏗️ 4. Codex Desktop UI & CLI Connection

Codex Desktop UI app (ChatGPT Desktop) backend par `app-server` mode mein chalti hai aur `~/.codex/config.toml` se read karti hai:

* **Base URL:** `http://localhost:8377/v1`
* **API Standard:** OpenAI Responses (`wire_api = "responses"`)
* **Auth:** `requires_openai_auth = false`
* **Model ID Formula:** `<peerId>@<modelName>` (e.g. `1d90f467689d499dc435e5744b4613c3203eb0aa@deepseek-v4-flash`)

### ⚡ 1-Click Interactive Model Switcher (`codex-model`):
Aapko kisi bhi file ko kholne ya edit karne ki zaroorat nahi hai. Terminal mein likhein:
```bash
codex-model
```
Yeh live network se tamam 60+ free models ki numbered list show karega. Sirf number dabayein (jaise `3` ya `7`), yeh automatically `config.toml` ko update kar dega! Uske baad Codex Desktop mein bas **New Chat (+)** start karein.

---

## 🎮 5. System Commands Cheatsheet

| Command | Maqsad |
| :--- | :--- |
| **`antseed-start`** | Web Studio UI + Proxy dono ko auto-start karega (`http://localhost:3000`). |
| **`antseed-web`** | Alias to `antseed-start`. |
| **`antseed-proxy`** | Sirf background OpenAI P2P proxy start karega (`:8377`) Codex/IDE ke liye. |
| **`antseed-stop`** | Saari running services ko cleanly band kar dega. |
| **`codex-model`** | Tamam live free models ki numbered list dikhayega aur 1 second mein model switch karega. |
| **`antseed-models`** | Terminal mein live network ke saare free models aur unki exact IDs print karega. |

---

## 🌐 6. Live Free Models & Direct Peer Pinning

* **Live Network Explorer:** [https://antseedstats.com/network?free=1](https://antseedstats.com/network?free=1)
* **Direct Peer Pinning:** Agar kabhi auto-routing slow ho ya credit error aaye, toh model ID ke aage peer ID lagayein: `<peerId>@<modelName>`. Is se request direct specific GPU server ke paas jati hai aur 100% free execute hoti hai!

---
*Created for Antseed AI Ecosystem — Universal Master Blueprint*
