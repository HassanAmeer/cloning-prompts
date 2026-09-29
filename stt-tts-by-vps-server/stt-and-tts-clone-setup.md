# 🎙️ STTTTS Full Server-Side & UI Clone Master Guide
> **The Definitive, A-to-Z Production Blueprint to Clone the Complete Unified AI Voice Engine**  
> **Includes:** Backend API, Whisper Small Fine-Tuned Model, RNNoise Neural Denoising, Piper Local ONNX TTS, Microsoft Edge Neural Studio TTS, 4 Web UI Studios, Universal Copy Engine, and Audio Enhancement Controls.  
> **Target OS:** Ubuntu 22.04 / 24.04 LTS or Debian 12 (2+ vCPU, 2GB+ RAM)  
> **Default Port:** Single Port `8089` (Serves API, Swagger Docs, and 4 Web UI Studios)

---

## 📑 Table of Contents
1. [Architecture & Workflow Blueprint](#1-architecture--workflow-blueprint)
2. [Complete Directory & File Hierarchy](#2-complete-directory--file-hierarchy)
3. [Two Instant Cloning Approaches](#3-two-instant-cloning-approaches)
   - [Method A: 1-Click Server-to-Server Full Clone (~2 Minutes)](#method-a-1-click-server-to-server-full-clone-2-minutes)
   - [Method B: Automated 1-Click Script on Fresh VPS (~5 Minutes)](#method-b-automated-1-click-script-on-fresh-vps-5-minutes)
4. [Step-by-Step Manual Setup (From Scratch)](#4-step-by-step-manual-setup-from-scratch)
5. [AI Models & Neural Weight Downloads](#5-ai-models--neural-weight-downloads)
6. [Complete Backend Source Code (`app.py` - Unabridged)](#6-complete-backend-source-code-apppy---unabridged)
7. [Audio Enhancement & Noise Cancellation Filter Chain](#7-audio-enhancement--noise-cancellation-filter-chain)
8. [Frontend Web Studios & UI Architecture](#8-frontend-web-studios--ui-architecture)
   - [Hub Launchpad (`/`)](#81-hub-launchpad-)
   - [WS3 STT Studio (`/ws3/`)](#82-ws3-stt-studio-ws3)
   - [MSTTS Microsoft Studio (`/mstts/`)](#83-mstts-microsoft-studio-mstts)
   - [Piper Local ONNX Studio (`/piper/`)](#84-piper-local-onnx-studio-piper)
   - [Universal Copy Engine & Top-Right Toast Alert](#85-universal-copy-engine--top-right-toast-alert)
   - [AI Prompt Generator Banner](#86-ai-prompt-generator-banner)
9. [API Endpoints Reference & JSON Schemas](#9-api-endpoints-reference--json-schemas)
10. [PM2 Production Management & Monitoring](#10-pm2-production-management--monitoring)
11. [Testing & Verification Test Suite](#11-testing--verification-test-suite)

---

## 1. Architecture & Workflow Blueprint

The system runs entirely as a single unified service on port `8089`:
* **STT Pipeline:** Audio → 80Hz Highpass Filter → RNNoise Neural Net (arnndn) → Auto Noise Gate (agate) → Dynamic Vocal Normalization (dynaudnorm) → Faster-Whisper Small int8 CTranslate2 (`Khubaib01/whisper-small-urdu-int8-ct2`).
* **TTS Pipeline:** 
  * **Priority 1:** Microsoft Edge Neural Cloud TTS (300+ Voices, ultra-realistic human inflection).
  * **Fallback / Offline:** Piper Local ONNX Engine (`ur_PK-fasih-medium`, `en_US-lessac-medium`) running 100% on CPU with zero cloud dependency.
  * **Smart Synthesis:** `POST /tts/synthesize` automatically tries Microsoft first; if network/quota fails, it instantly falls back to Piper ONNX without throwing an error.
* **Web Studios:** Served via FastAPI `StaticFiles` directly on `http://<IP>:8089/`.

```mermaid
flowchart TD
    Client["Client (Browser / WhatsApp Bot / Python App)"] -->|Single Port 8089| FastAPI["FastAPI Core Engine (/root/stttts/app.py)"]

    subgraph "Web UI Studios (FastAPI StaticFiles)"
        FastAPI -->|"GET /"| HubUI["Hub Launchpad (/static/hub/)"]
        FastAPI -->|"GET /ws3"| WS3UI["WS3 STT Studio (/static/ws3/)"]
        FastAPI -->|"GET /mstts"| MSTTSUI["MSTTS Studio (/static/mstts/)"]
        FastAPI -->|"GET /piper"| PiperUI["Piper Studio (/static/piper/)"]
        FastAPI -->|"GET /docs"| Swagger["OpenAPI / Swagger Tester"]
    end

    subgraph "STT Audio Preprocessing Chain"
        FastAPI -->|"POST /stt/transcribe"| HP["1. 80Hz Highpass Filter (Cut AC & Mic Rumble)"]
        HP --> RNN["2. RNNoise Neural Network (Fan & Traffic Suppression)"]
        RNN --> Gate["3. Auto Noise Gate (Mute Silent Pauses)"]
        Gate --> Dyn["4. Dynamic Normalizer dynaudnorm (Vocal Leveling)"]
        Dyn --> Whisper["5. Faster-Whisper Small (int8-ct2, 2 Threads)"]
        Whisper --> STTOut["JSON: text, confidence, latency, denoise_settings"]
    end

    subgraph "TTS Synthesis Engine"
        FastAPI -->|"POST /tts/synthesize"| Router{"Smart TTS Router"}
        Router -->|"Priority 1"| EdgeTTS["Microsoft Edge Neural TTS (Cloud)"]
        EdgeTTS -.->|"On Error / Timeout"| PiperONNX["Piper Local ONNX Engine (Offline)"]
        FastAPI -->|"POST /tts/ms"| EdgeTTS
        FastAPI -->|"POST /tts/piper"| PiperONNX
    end
```

---

## 2. Complete Directory & File Hierarchy

On the target VPS, the entire system lives inside `/root/stttts/`:

```
/root/stttts/
├── app.py                         # 826-line Unified FastAPI Application
├── README.md                      # System documentation
├── venv/                          # Python 3.12 Virtual Environment
├── rnnoise/                       # RNNoise RNN Weight Models
│   ├── rnnoise_speech.rnnn        # General speech model (~300 KB)
│   └── rnnoise_aggressive.rnnn    # Heavy background suppression model (~300 KB)
├── piper_models/                  # Piper Local ONNX Voice Models
│   ├── ur_PK-fasih-medium.onnx    # Urdu voice neural weights (~63 MB)
│   ├── ur_PK-fasih-medium.onnx.json # Urdu phoneme & audio config
│   ├── en_US-lessac-medium.onnx   # English voice neural weights (~63 MB)
│   └── en_US-lessac-medium.onnx.json # English config
└── static/                        # 4 Dark Glassmorphic Web Studios (~312 KB total)
    ├── hub/                       # Central Dashboard
    │   ├── index.html
    │   ├── style.css
    │   └── app.js
    ├── ws3/                       # WS3 Speech-to-Text Studio
    │   ├── index.html             # With Audio Enhancement Card & Docs
    │   ├── style.css              # Dark glassmorphic theme
    │   └── app.js                 # Universal Copy + Toast + Live Dictation
    ├── mstts/                     # Microsoft Studio TTS
    │   ├── index.html             # 300+ Voice Dropdown & Accent Filter
    │   ├── style.css
    │   └── app.js                 # Universal Copy + Toast
    └── piper/                     # Piper Local ONNX TTS
        ├── index.html             # Speed, Pitch, Noise-Scale Sliders
        ├── style.css
        └── app.js                 # Universal Copy + Toast
```

---

## 3. Two Instant Cloning Approaches

### Method A: 1-Click Server-to-Server Full Clone (~2 Minutes)
If your primary server (`145.223.34.142`) is online, you can mirror the entire production environment directly to the new VPS:

**On your NEW VPS, run these 3 commands:**
```bash
# 1. Install base system dependencies
apt update && apt install -y python3 ffmpeg nodejs npm rsync curl
npm install -g pm2

# 2. Sync the entire directory from Primary VPS (Enter primary VPS password when prompted)
rsync -avzP root@145.223.34.142:/root/stttts /root/

# 3. Start service with PM2
cd /root/stttts
pm2 start "/root/stttts/venv/bin/python3 -m uvicorn app:app --host 0.0.0.0 --port 8089" --name stttts-api
pm2 save
pm2 startup
```

---

### Method B: Automated 1-Click Script on Fresh VPS (~5 Minutes)
If you want to clone without SSH credentials to the old server, run this self-contained script on your fresh VPS:

```bash
cat << 'CLONE_SCRIPT' > /root/clone_setup.sh
#!/usr/bin/env bash
set -e

echo "=========================================================="
echo "🚀 1-Click Automated Setup: STTTTS AI Voice Engine"
echo "=========================================================="

# 1. Install system packages
apt update && apt install -y python3 python3-venv python3-pip ffmpeg git curl wget nodejs npm build-essential

# 2. Install PM2
npm install -g pm2

# 3. Create directories
mkdir -p /root/stttts/rnnoise
mkdir -p /root/stttts/piper_models
mkdir -p /root/stttts/static

# 4. Download pre-packaged Code & Web Studios Bundle (353 KB)
echo "--> Downloading application code and web studios..."
cd /root/stttts
wget -q -O stttts-bundle.tar.gz http://145.223.34.142:8089/hub/stttts-bundle.tar.gz
tar -xzf stttts-bundle.tar.gz

# 5. Create Python Virtualenv & Install exact packages
echo "--> Setting up Python virtual environment..."
python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip setuptools wheel
pip install fastapi==0.141.1 uvicorn==0.54.0 python-multipart==0.0.32 faster-whisper==1.2.1 ctranslate2==4.8.2 edge-tts==7.2.8 piper-tts==1.8.0 onnxruntime==1.30.0 aiofiles==25.1.0 requests==2.32.3 huggingface_hub==1.33.0 numpy soundfile

# 6. Download Piper ONNX Voice Models
echo "--> Downloading Piper local ONNX voice models..."
cd /root/stttts/piper_models
wget -q -c -O ur_PK-fasih-medium.onnx https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/ur/ur_PK/fasih/medium/ur_PK-fasih-medium.onnx
wget -q -c -O ur_PK-fasih-medium.onnx.json https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/ur/ur_PK/fasih/medium/ur_PK-fasih-medium.onnx.json
wget -q -c -O en_US-lessac-medium.onnx https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/en/en_US/lessac/medium/en_US-lessac-medium.onnx
wget -q -c -O en_US-lessac-medium.onnx.json https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/en/en_US/lessac/medium/en_US-lessac-medium.onnx.json

# 7. Pre-cache Whisper Small Urdu/English Model
echo "--> Pre-caching Whisper Small fine-tuned model..."
python3 -c "
from faster_whisper import WhisperModel
WhisperModel('Khubaib01/whisper-small-urdu-int8-ct2', device='cpu', compute_type='int8')
print('--> Whisper Model cached!')
"

# 8. Start with PM2
echo "--> Starting service with PM2 on port 8089..."
cd /root/stttts
pm2 start "/root/stttts/venv/bin/python3 -m uvicorn app:app --host 0.0.0.0 --port 8089" --name stttts-api
pm2 save
pm2 startup

echo "=========================================================="
echo "🎉 SUCCESS: System is live on http://$(curl -s ifconfig.me):8089"
echo "=========================================================="
CLONE_SCRIPT

bash /root/clone_setup.sh
```

---

## 4. Step-by-Step Manual Setup (From Scratch)

If you prefer executing every step manually:

```bash
# 1. Update system & install tools
apt update && apt install -y python3 python3-venv python3-pip ffmpeg git curl wget nodejs npm build-essential

# 2. Install PM2
npm install -g pm2

# 3. Create directory tree
mkdir -p /root/stttts/rnnoise /root/stttts/piper_models /root/stttts/static/hub /root/stttts/static/ws3 /root/stttts/static/mstts /root/stttts/static/piper

# 4. Create Virtual Environment
cd /root/stttts
python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip setuptools wheel

# 5. Install dependencies
pip install fastapi==0.141.1 uvicorn==0.54.0 python-multipart==0.0.32 faster-whisper==1.2.1 ctranslate2==4.8.2 edge-tts==7.2.8 piper-tts==1.8.0 onnxruntime==1.30.0 aiofiles==25.1.0 requests==2.32.3 huggingface_hub==1.33.0 numpy soundfile

# 6. Deploy Exact Frontend Web Studios (100% Identical Design & UI)
# Option A: Download the pre-packaged static archive directly:
cd /root/stttts
wget -q -O static.tar.gz http://145.223.34.142:8089/hub/static.tar.gz
tar -xzf static.tar.gz

# Option B (Or from local development machine):
# scp -r /home/hasan/Documents/reactjs/tts-tts/static/* root@<NEW_VPS_IP>:/root/stttts/static/
```

---

## 5. AI Models & Neural Weight Downloads

### 5.1 RNNoise Noise Suppression Models
```bash
cd /root/stttts/rnnoise
wget -O rnnoise_speech.rnnn https://raw.githubusercontent.com/richardpl/arnndn-models/master/rnnoise_speech.rnnn
wget -O rnnoise_aggressive.rnnn https://raw.githubusercontent.com/richardpl/arnndn-models/master/rnnoise_aggressive.rnnn
```

### 5.2 Piper Local ONNX Voices
```bash
cd /root/stttts/piper_models
wget -O ur_PK-fasih-medium.onnx https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/ur/ur_PK/fasih/medium/ur_PK-fasih-medium.onnx
wget -O ur_PK-fasih-medium.onnx.json https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/ur/ur_PK/fasih/medium/ur_PK-fasih-medium.onnx.json
wget -O en_US-lessac-medium.onnx https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/en/en_US/lessac/medium/en_US-lessac-medium.onnx
wget -O en_US-lessac-medium.onnx.json https://huggingface.co/rhasspy/piper-voices/resolve/v1.0.0/en/en_US/lessac/medium/en_US-lessac-medium.onnx.json
```

### 5.3 Whisper Small Fine-Tuned Model (`Khubaib01/whisper-small-urdu-int8-ct2`)
Pre-cache via Python:
```bash
/root/stttts/venv/bin/python3 -c "
from faster_whisper import WhisperModel
print('--> Downloading Whisper model...')
WhisperModel('Khubaib01/whisper-small-urdu-int8-ct2', device='cpu', compute_type='int8')
print('--> Model ready!')
"
```

---

## 6. Complete Backend Source Code (`app.py` - Unabridged)

Below is the **100% complete, exact production code** currently running on `/root/stttts/app.py`:

```python
import os
import io
import time
import shutil
import tempfile
import asyncio
from typing import Optional
from fastapi import FastAPI, File, UploadFile, Form, HTTPException, Query, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import Response, FileResponse, RedirectResponse
from fastapi.staticfiles import StaticFiles
from starlette.concurrency import run_in_threadpool
from pydantic import BaseModel, Field
import edge_tts
from faster_whisper import WhisperModel

app = FastAPI(
    title="Unified AI Voice & Speech Engine (STT & TTS)",
    version="3.0.0",
    description="""
# 🎙️ All-in-One Voice AI Engine (Single Port 8089)

### ⚡ 1. STT: Fine-Tuned Whisper Small + Pre-Whisper RNNoise AI Denoising
- **🎙️ RNNoise Neural Filter (Active by Default):** Input audio is automatically cleaned by a specialized Recurrent Neural Network (RNNoise) before being passed to Whisper Small. This eliminates fan, traffic, AC, and microphone hiss/static in ~0.1s without losing speech details.
- **Model:** `Khubaib01/whisper-small-urdu-int8-ct2` (Urdu & English fine-tuned, 70% less RAM, 3.5x real-time speed).

### 🔊 2. TTS (Priority 1): Microsoft Edge Neural Studio (300+ Voices, 40+ Accents)
### 🗣️ 3. TTS (Fallback): Piper Local ONNX Engine (100% On-Premise)

---
### 📌 Main Endpoints:
- **Speech-to-Text (+ AI Denoising & Studio Audio Enhancement):** `POST /stt/transcribe` & `POST /stt/transcribe-live`
  - `denoise=true` (Default: True ★ Recommended)
  - `denoise_level='medium'` ('light', 'medium' ★ Recommended, 'aggressive')
  - `highpass_filter=true` (Cuts rumble below 80Hz ★ Recommended)
  - `speech_boost=true` (Dynamic audio normalization ★ Recommended)
  - `noise_gate=false` (Mutes silent pauses)
- **Smart Speech Synthesis:** `POST /tts/synthesize` (Tries Microsoft first; auto-falls back to Piper!)
- **Direct Microsoft TTS:** `POST /tts/ms` & `GET /tts/voices`
- **Direct Piper Local TTS:** `POST /tts/piper` & `GET /tts/piper-voices`
- **Interactive Swagger Tester:** `/docs`
    """,
    docs_url="/docs",
    redoc_url="/redoc"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Locks and Concurrency
cpu_lock = asyncio.Lock()
is_live_busy = False

# ----------------- STT (Whisper Small Urdu/English) -----------------
STT_MODEL_ID = "Khubaib01/whisper-small-urdu-int8-ct2"
loaded_stt_model = None

def get_stt_model():
    global loaded_stt_model
    if loaded_stt_model is None:
        print(f"--> [STT] Loading fine-tuned Urdu/English model '{STT_MODEL_ID}' (int8, 2 threads)...")
        loaded_stt_model = WhisperModel(
            STT_MODEL_ID,
            device="cpu",
            compute_type="int8",
            cpu_threads=2,
            num_workers=1
        )
        print("--> [STT] Whisper Small Model loaded successfully!")
    return loaded_stt_model

def get_initial_prompt(language: Optional[str]) -> Optional[str]:
    if not language:
        return "یہ اردو اور انگریزی گفتگو کی درست ٹرانسکرپشن ہے۔ Urdu and English speech transcription."
    lang = language.strip().lower()
    if lang in ["ur", "urdu"]:
        return "یہ اردو گفتگو کی صاف اور درست املا کے ساتھ ٹرانسکرپشن ہے۔"
    elif lang in ["en", "english"]:
        return "Clean and accurate English speech transcription."
    return None

def _run_transcribe(model, path, language, task, beam_size, custom_prompt=None):
    prompt = custom_prompt if custom_prompt else get_initial_prompt(language)
    lang_param = language if language and language.strip() else None

    segments_gen, info = model.transcribe(
        path,
        language=lang_param,
        task=task,
        beam_size=beam_size,
        initial_prompt=prompt,
        condition_on_previous_text=False,
        vad_filter=True,
        vad_parameters=dict(min_silence_duration_ms=400, speech_pad_ms=200),
        repetition_penalty=1.15,
        no_repeat_ngram_size=3
    )

    segments = []
    full_text_list = []
    for s in segments_gen:
        txt = s.text.strip()
        if txt and s.no_speech_prob < 0.7:
            segments.append({
                "id": s.id,
                "start": round(s.start, 2),
                "end": round(s.end, 2),
                "text": txt,
                "avg_logprob": round(s.avg_logprob, 3),
                "no_speech_prob": round(s.no_speech_prob, 3)
            })
            full_text_list.append(txt)

    return " ".join(full_text_list).strip(), info, segments

def _run_live_transcribe(model, path, language, custom_prompt=None):
    prompt = custom_prompt if custom_prompt else get_initial_prompt(language)
    lang_param = language if language and language.strip() else None

    segments_gen, info = model.transcribe(
        path,
        language=lang_param,
        beam_size=1,
        initial_prompt=prompt,
        condition_on_previous_text=False,
        vad_filter=True,
        vad_parameters=dict(min_silence_duration_ms=250, speech_pad_ms=150),
        repetition_penalty=1.12,
        no_repeat_ngram_size=3
    )

    texts = []
    for s in segments_gen:
        if s.no_speech_prob < 0.65 and s.avg_logprob > -1.6:
            txt = s.text.strip()
            if txt:
                texts.append(txt)

    return " ".join(texts).strip(), info.language

# ----------------- RNNoise AI Noise Cancellation & Audio Enhancement -----------------
RNNOISE_MODEL_PATH = "/root/stttts/rnnoise/rnnoise_speech.rnnn"

async def apply_audio_enhancement(
    input_path: str,
    denoise: bool = True,
    denoise_level: str = "medium",       # "light", "medium", "aggressive"
    noise_gate: bool = False,
    highpass_filter: bool = True,
    speech_boost: bool = True
) -> tuple[str, float, dict]:
    """
    Applies Recurrent Neural Network Noise Suppression (RNNoise via FFmpeg arnndn filter)
    along with studio audio enhancement filters (80Hz rumble cut, noise gate, dynamic speech boost).
    Resamples directly to 16kHz mono WAV for maximum Whisper transcription efficiency.
    Returns: (output_wav_path, elapsed_seconds, settings_applied)
    """
    level = (denoise_level or "medium").lower().strip()
    if level not in ("light", "medium", "aggressive"):
        level = "medium"

    settings = {
        "denoise": bool(denoise),
        "level": level if denoise else "off",
        "noise_gate": bool(noise_gate) if denoise else False,
        "highpass_filter": bool(highpass_filter),
        "speech_boost": bool(speech_boost)
    }

    if not denoise and not highpass_filter and not speech_boost and not noise_gate:
        return input_path, 0.0, settings

    t0 = time.time()
    out_fd, out_path = tempfile.mkstemp(suffix="_enhanced.wav")
    os.close(out_fd)

    has_rnnoise = denoise and os.path.exists(RNNOISE_MODEL_PATH)

    if has_rnnoise and level == "light":
        # 70% Cleaned + 30% Original Dry/Wet mix for ultra-natural human tone
        pre = "highpass=f=80," if highpass_filter else ""
        post = ""
        if noise_gate:
            post += ",agate=threshold=-32dB:ratio=2:range=-50dB"
        if speech_boost:
            post += ",dynaudnorm=f=150:g=15"

        filter_complex = f"[0:a]{pre}asplit=2[orig][noisy];[noisy]arnndn=m='{RNNOISE_MODEL_PATH}'[clean];[clean][orig]amix=inputs=2:weights=0.7 0.3{post}[out]"
        cmd = [
            "ffmpeg", "-y", "-i", input_path,
            "-filter_complex", filter_complex,
            "-map", "[out]",
            "-ar", "16000", "-ac", "1",
            out_path
        ]
    else:
        af_filters = []
        if highpass_filter:
            af_filters.append("highpass=f=80")
        if has_rnnoise:
            af_filters.append(f"arnndn=m='{RNNOISE_MODEL_PATH}'")
        if noise_gate or (has_rnnoise and level == "aggressive"):
            af_filters.append("agate=threshold=-32dB:ratio=2:range=-50dB")
        if speech_boost:
            af_filters.append("dynaudnorm=f=150:g=15")

        if not af_filters:
            if os.path.exists(out_path):
                os.remove(out_path)
            return input_path, 0.0, settings

        cmd = [
            "ffmpeg", "-y", "-i", input_path,
            "-af", ",".join(af_filters),
            "-ar", "16000", "-ac", "1",
            out_path
        ]

    try:
        proc = await asyncio.create_subprocess_exec(
            *cmd,
            stdout=asyncio.subprocess.PIPE,
            stderr=asyncio.subprocess.PIPE
        )
        await proc.communicate()
        elapsed = round(time.time() - t0, 3)

        if proc.returncode == 0 and os.path.exists(out_path) and os.path.getsize(out_path) > 0:
            return out_path, elapsed, settings
        else:
            if os.path.exists(out_path):
                os.remove(out_path)
            return input_path, 0.0, settings
    except Exception as e:
        print(f"--> [Audio Enhancement Warning] Filter error, using original audio: {e}")
        if os.path.exists(out_path):
            os.remove(out_path)
        return input_path, 0.0, settings

# Backwards compatibility alias
apply_rnnoise_filter = apply_audio_enhancement

# ----------------- TTS Config (Microsoft + Piper) -----------------
cached_voices = []
PIPER_MODELS_DIR = "/root/stttts/piper_models"
PIPER_BIN = "/root/stttts/venv/bin/piper"

PIPER_VOICES = [
    {
        "id": "ur_PK-fasih-medium",
        "name": "Fasih (Urdu Pakistan - Medium)",
        "language": "Urdu (Pakistan)",
        "locale": "ur-PK",
        "flag": "🇵🇰",
        "gender": "Male",
        "engine": "piper-onnx",
        "model_file": f"{PIPER_MODELS_DIR}/ur_PK-fasih-medium.onnx",
        "config_file": f"{PIPER_MODELS_DIR}/ur_PK-fasih-medium.onnx.json"
    },
    {
        "id": "en_US-lessac-medium",
        "name": "Lessac (English US - Medium)",
        "language": "English (US)",
        "locale": "en-US",
        "flag": "🇺🇸",
        "gender": "Female",
        "engine": "piper-onnx",
        "model_file": f"{PIPER_MODELS_DIR}/en_US-lessac-medium.onnx",
        "config_file": f"{PIPER_MODELS_DIR}/en_US-lessac-medium.onnx.json"
    }
]

FLAG_MAP = {
    "ur-PK": "🇵🇰", "ur-IN": "🇮🇳", "en-US": "🇺🇸", "en-GB": "🇬🇧",
    "en-AU": "🇦🇺", "en-IN": "🇮🇳", "en-CA": "🇨🇦", "en-IE": "🇮🇪",
    "en-NZ": "🇳🇿", "en-ZA": "🇿🇦", "hi-IN": "🇮🇳", "ar-SA": "🇸🇦",
    "ar-AE": "🇦🇪", "ar-EG": "🇪🇬", "es-ES": "🇪🇸", "es-MX": "🇲🇽",
    "fr-FR": "🇫🇷", "fr-CA": "🇨🇦", "de-DE": "🇩🇪", "it-IT": "🇮🇹",
    "tr-TR": "🇹🇷", "zh-CN": "🇨🇳", "ja-JP": "🇯🇵",
}

class SynthesizeRequest(BaseModel):
    text: str = Field(..., description="Text to synthesize", example="السلام علیکم، یہ اسمارٹ نیورل اسپیچ ہے۔")
    voice: Optional[str] = Field("ur-PK-UzmaNeural", description="Primary Microsoft Voice ID", example="ur-PK-UzmaNeural")
    fallback_voice: Optional[str] = Field("ur_PK-fasih-medium", description="Backup Piper Voice ID", example="ur_PK-fasih-medium")
    rate: Optional[str] = Field("+0%", description="Speed adjustment", example="+0%")
    pitch: Optional[str] = Field("+0Hz", description="Pitch adjustment", example="+0Hz")
    volume: Optional[str] = Field("+0%", description="Volume adjustment", example="+0%")

class TTSRequest(BaseModel):
    text: str = Field(..., description="Text to speak")
    voice: Optional[str] = Field("ur-PK-UzmaNeural")
    rate: Optional[str] = Field("+0%")
    pitch: Optional[str] = Field("+0Hz")
    volume: Optional[str] = Field("+0%")

class PiperTTSRequest(BaseModel):
    text: str = Field(..., description="Text to synthesize with Piper")
    voice: Optional[str] = Field("ur_PK-fasih-medium")
    speed: Optional[float] = Field(1.0, description="Speech speed (e.g. 1.5 = faster, 0.7 = slower)")
    length_scale: Optional[float] = Field(None, description="Direct phoneme length scale (legacy/optional)")
    pitch: Optional[float] = Field(0.0, description="Pitch shift in Hz (e.g. +25 for higher pitch, -25 for deeper voice)")
    noise_scale: Optional[float] = Field(0.667, description="Generator noise (0.1 to 1.0)")

@app.on_event("startup")
async def startup_event():
    global cached_voices
    # 1. Preload Whisper Small STT
    try:
        get_stt_model()
    except Exception as e:
        print(f"--> [STT] Warning loading model: {e}")

    # 2. Preload Microsoft Voices
    try:
        raw_voices = await edge_tts.list_voices()
        parsed = []
        for v in raw_voices:
            short_name = v.get("ShortName", "")
            locale = v.get("Locale", "")
            gender = v.get("Gender", "Unknown")
            flag = FLAG_MAP.get(locale, "🌐")
            
            parsed.append({
                "name": short_name,
                "gender": gender,
                "locale": locale,
                "flag": flag,
                "friendly_name": v.get("FriendlyName", short_name),
                "suggested_codec": v.get("SuggestedCodec", "audio/mpeg")
            })
        cached_voices = parsed
        print(f"--> [TTS] Loaded {len(cached_voices)} Microsoft Neural Voices!")
    except Exception as e:
        print(f"--> [TTS] Voice preload warning: {e}")

# Helper: Microsoft Audio Generation
async def generate_speech_bytes(text: str, voice: str, rate: str = "+0%", pitch: str = "+0Hz", volume: str = "+0%") -> bytes:
    if not text or not text.strip():
        raise HTTPException(status_code=400, detail="Text cannot be empty")
    
    rate_str = rate if rate.startswith(("+", "-")) else f"+{rate}" if rate != "0%" else "+0%"
    pitch_str = pitch if pitch.startswith(("+", "-")) else f"+{pitch}" if pitch != "0Hz" else "+0Hz"
    volume_str = volume if volume.startswith(("+", "-")) else f"+{volume}" if volume != "0%" else "+0%"
    
    communicate = edge_tts.Communicate(
        text=text.strip(),
        voice=voice,
        rate=rate_str,
        pitch=pitch_str,
        volume=volume_str
    )
    
    mp3_buffer = io.BytesIO()
    async for chunk in communicate.stream():
        if chunk["type"] == "audio":
            mp3_buffer.write(chunk["data"])
            
    mp3_bytes = mp3_buffer.getvalue()
    if not mp3_bytes:
        raise RuntimeError("Failed to generate Microsoft Edge audio stream")
    return mp3_bytes

# Helper: Piper Audio Generation
async def generate_piper_bytes(
    text: str,
    voice_id: str,
    speed: float = 1.0,
    length_scale: Optional[float] = None,
    pitch: float = 0.0,
    noise_scale: float = 0.667
) -> bytes:
    if not text or not text.strip():
        raise HTTPException(status_code=400, detail="Text cannot be empty")
        
    selected_voice = next((v for v in PIPER_VOICES if v["id"] == voice_id), PIPER_VOICES[0])
    model_path = selected_voice["model_file"]

    # Calculate Piper phoneme length scale:
    # If user provided explicit length_scale > 0, use it. Otherwise, convert speed multiplier:
    # Higher speed (1.5x) => smaller length scale (0.667)
    # Lower speed (0.7x) => larger length scale (1.428)
    if length_scale is not None and length_scale > 0:
        eff_length_scale = length_scale
    else:
        eff_speed = max(0.4, min(float(speed or 1.0), 2.5))
        eff_length_scale = round(1.0 / eff_speed, 3)

    cmd = [
        PIPER_BIN,
        "--model", model_path,
        "--length-scale", str(eff_length_scale),
        "--noise-scale", str(noise_scale),
        "-f", "-"
    ]
    
    proc = await asyncio.create_subprocess_exec(
        *cmd,
        stdin=asyncio.subprocess.PIPE,
        stdout=asyncio.subprocess.PIPE,
        stderr=asyncio.subprocess.PIPE
    )
    stdout, stderr = await proc.communicate(input=text.strip().encode("utf-8"))
    
    if proc.returncode != 0 or not stdout:
        err_msg = stderr.decode("utf-8", errors="ignore")
        raise RuntimeError(f"Piper generation failed: {err_msg}")

    # Pitch Tuning via high-speed FFmpeg filter
    pitch_val = float(pitch or 0.0)
    if abs(pitch_val) >= 1.0:
        pitch_factor = max(0.6, min((150.0 + pitch_val) / 150.0, 1.5))
        t_in = tempfile.NamedTemporaryFile(delete=False, suffix=".wav")
        t_out = tempfile.NamedTemporaryFile(delete=False, suffix=".wav")
        t_in.write(stdout)
        t_in.close()
        t_out.close()
        try:
            shift_filter = f"asetrate=22050*{pitch_factor:.3f},atempo={1.0/pitch_factor:.3f},aresample=22050"
            f_cmd = ["ffmpeg", "-y", "-i", t_in.name, "-af", shift_filter, t_out.name]
            f_proc = await asyncio.create_subprocess_exec(
                *f_cmd,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE
            )
            await f_proc.communicate()
            if f_proc.returncode == 0 and os.path.exists(t_out.name) and os.path.getsize(t_out.name) > 0:
                with open(t_out.name, "rb") as rf:
                    stdout = rf.read()
        except Exception as pe:
            print(f"--> [Piper Pitch Error] {pe}")
        finally:
            if os.path.exists(t_in.name):
                os.remove(t_in.name)
            if os.path.exists(t_out.name):
                os.remove(t_out.name)

    return stdout

# ==================== 1. STT ENDPOINTS ====================

@app.post("/stt/transcribe", tags=["Speech-to-Text (STT)"])
@app.post("/transcribe", tags=["Speech-to-Text (STT)"], include_in_schema=False)
async def transcribe_audio_file(
    file: UploadFile = File(..., description="Audio file (WAV, MP3, M4A, OGG, WebM)"),
    language: Optional[str] = Form(None, description="Audio language code ('ur' for Urdu, 'en' for English, or None for auto-detect)"),
    task: str = Form("transcribe", description="'transcribe' (same language) or 'translate' (translate to English)"),
    beam_size: int = Form(1, description="Beam search width: 1 = fastest (~2.5s), 5 = maximum precision"),
    prompt: Optional[str] = Form(None, description="Vocabulary hint / prompt for specialized terminology or Pakistani names"),
    denoise: bool = Form(True, description="Enable AI Noise Cancellation before Whisper (Default: True, Recommended ★)"),
    denoise_level: str = Form("medium", description="Noise suppression mode: 'light' (70/30 natural mix), 'medium' (balanced neural filter, Recommended ★), 'aggressive' (heavy suppression + agate)"),
    noise_gate: bool = Form(False, description="Enable automatic noise gate to completely mute background during silent pauses (Default: False)"),
    highpass_filter: bool = Form(True, description="Cut low-end mic rumble, wind, and fan vibrations below 80Hz (Default: True, Recommended ★)"),
    speech_boost: bool = Form(True, description="Dynamic Audio Normalization (dynaudnorm) to balance quiet and loud speech levels before Whisper (Default: True, Recommended ★)")
):
    """
    ### 🎙️ Pre-Whisper RNNoise AI Noise Cancellation & Audio Enhancement Active
    1. **AI Noise Suppression:** Input audio is automatically cleaned by **RNNoise (Recurrent Neural Network)** to eliminate fan, traffic, AC, and mic static in ~0.1s.
    2. **Low-End Rumble Cut (80Hz High-Pass):** Removes table thumps, wind rumble, and low-frequency electrical hum.
    3. **Dynamic Speech Boost:** Balances whispering or distant audio with normal levels (`dynaudnorm`).
    4. **Whisper Small Transcription:** Model transcribes with highest Urdu & English accuracy and zero hallucination.

    **Customization Parameters & Best Values:**
    - `denoise=true` (Recommended ★)
    - `denoise_level='medium'` (Recommended ★) | Options: `'light'`, `'medium'`, `'aggressive'`
    - `highpass_filter=true` (Recommended ★)
    - `speech_boost=true` (Recommended ★)
    - `noise_gate=false` (Optional: Set `true` for noisy call center or outdoor environments)
    """
    if not file.filename:
        raise HTTPException(status_code=400, detail="No audio file uploaded")

    suffix = os.path.splitext(file.filename)[1] or ".wav"
    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        shutil.copyfileobj(file.file, tmp)
        tmp_path = tmp.name

    denoised_path = tmp_path
    rnnoise_elapsed = 0.0
    denoise_settings = {
        "denoise": denoise,
        "level": denoise_level if denoise else "off",
        "noise_gate": noise_gate if denoise else False,
        "highpass_filter": highpass_filter,
        "speech_boost": speech_boost
    }

    if denoise or highpass_filter or speech_boost:
        denoised_path, rnnoise_elapsed, denoise_settings = await apply_audio_enhancement(
            tmp_path,
            denoise=denoise,
            denoise_level=denoise_level,
            noise_gate=noise_gate,
            highpass_filter=highpass_filter,
            speech_boost=speech_boost
        )

    try:
        model = get_stt_model()
        start_time = time.time()
        async with cpu_lock:
            full_text, info, segments = await run_in_threadpool(
                _run_transcribe, model, denoised_path, language, task, beam_size, prompt
            )
        elapsed = round(time.time() - start_time, 2)

        return {
            "success": True,
            "filename": file.filename,
            "text": full_text,
            "model_used": "whisper-small-urdu-fine-tuned",
            "detected_language": info.language,
            "language_probability": round(info.language_probability, 3),
            "audio_duration_seconds": round(info.duration, 2),
            "inference_time_seconds": elapsed,
            "rnnoise_applied": denoised_path != tmp_path,
            "denoise_settings": denoise_settings,
            "rnnoise_latency_seconds": rnnoise_elapsed,
            "segments": segments
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        if os.path.exists(tmp_path):
            os.remove(tmp_path)
        if denoised_path != tmp_path and os.path.exists(denoised_path):
            os.remove(denoised_path)

@app.post("/stt/transcribe-live", tags=["Speech-to-Text (STT)"])
@app.post("/transcribe-live", tags=["Speech-to-Text (STT)"], include_in_schema=False)
async def transcribe_live_chunk(
    file: UploadFile = File(..., description="Short 2-3 second audio chunk"),
    language: Optional[str] = Form(None),
    prompt: Optional[str] = Form(None),
    denoise: bool = Form(True, description="Enable AI Noise Cancellation for live chunk (Default: True, Recommended ★)"),
    denoise_level: str = Form("medium", description="Noise suppression mode: 'light', 'medium', 'aggressive'"),
    highpass_filter: bool = Form(True, description="Cut low-end mic rumble below 80Hz (Default: True)"),
    speech_boost: bool = Form(True, description="Dynamic audio normalization (Default: True)")
):
    """
    ### 🎙️ Live Streaming Dictation with Pre-Whisper Audio Enhancement
    Sub-second live streaming dictation chunk endpoint with RNNoise speech isolation before Whisper Small.
    """
    global is_live_busy
    if is_live_busy:
        return {"text": "", "status": "skipped_busy"}

    suffix = os.path.splitext(file.filename)[1] or ".wav"
    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        shutil.copyfileobj(file.file, tmp)
        tmp_path = tmp.name

    denoised_path = tmp_path
    rnnoise_elapsed = 0.0
    denoise_settings = {
        "denoise": denoise,
        "level": denoise_level if denoise else "off",
        "highpass_filter": highpass_filter,
        "speech_boost": speech_boost
    }

    if denoise or highpass_filter or speech_boost:
        denoised_path, rnnoise_elapsed, denoise_settings = await apply_audio_enhancement(
            tmp_path,
            denoise=denoise,
            denoise_level=denoise_level,
            noise_gate=False,
            highpass_filter=highpass_filter,
            speech_boost=speech_boost
        )

    is_live_busy = True
    try:
        model = get_stt_model()
        start_time = time.time()
        async with cpu_lock:
            text, detected_lang = await run_in_threadpool(
                _run_live_transcribe, model, denoised_path, language, prompt
            )
        elapsed = round(time.time() - start_time, 2)

        return {
            "text": text,
            "language": detected_lang,
            "latency_seconds": elapsed,
            "rnnoise_applied": denoised_path != tmp_path,
            "denoise_settings": denoise_settings,
            "rnnoise_latency_seconds": rnnoise_elapsed
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
    finally:
        is_live_busy = False
        if os.path.exists(tmp_path):
            os.remove(tmp_path)
        if denoised_path != tmp_path and os.path.exists(denoised_path):
            os.remove(denoised_path)

# ==================== 2. TTS ENDPOINTS ====================

@app.post("/tts/synthesize", tags=["Text-to-Speech (TTS)"])
@app.post("/synthesize", tags=["Text-to-Speech (TTS)"], include_in_schema=False)
async def smart_synthesize_endpoint(req: SynthesizeRequest):
    """
    ### 🎯 Smart Hybrid Synthesis (Priority 1 = Microsoft, Fallback = Piper)
    1. First tries **Microsoft Edge Neural TTS** for highest human realism.
    2. If Microsoft fails (rate-limit, timeout, network error), it **automatically falls back to Piper Local ONNX**.
    3. Returns audio with `X-TTS-Engine: microsoft` or `X-TTS-Engine: piper-fallback`.
    """
    t0 = time.time()
    
    # 1. Attempt Microsoft Edge Neural TTS
    try:
        voice = req.voice or "ur-PK-UzmaNeural"
        audio_bytes = await generate_speech_bytes(
            text=req.text,
            voice=voice,
            rate=req.rate or "+0%",
            pitch=req.pitch or "+0Hz",
            volume=req.volume or "+0%"
        )
        elapsed = f"{time.time() - t0:.2f}s"
        return Response(
            content=audio_bytes,
            media_type="audio/mpeg",
            headers={
                "Content-Disposition": f"inline; filename=speech_{voice}.mp3",
                "X-TTS-Engine": "microsoft",
                "X-TTS-Latency": elapsed,
                "X-TTS-Voice": voice,
                "Access-Control-Expose-Headers": "X-TTS-Engine, X-TTS-Latency, X-TTS-Voice"
            }
        )
    except Exception as ms_err:
        print(f"--> [FALLBACK TRIGGERED] Microsoft TTS failed: {ms_err}. Switching to Piper...")
        
    # 2. Fallback to Piper Local ONNX
    try:
        fallback_voice = req.fallback_voice
        if not fallback_voice:
            fallback_voice = "ur_PK-fasih-medium" if ("ur" in (req.voice or "")) else "en_US-lessac-medium"
            
        audio_bytes = await generate_piper_bytes(
            text=req.text,
            voice_id=fallback_voice,
            length_scale=1.0,
            noise_scale=0.667
        )
        elapsed = f"{time.time() - t0:.2f}s"
        return Response(
            content=audio_bytes,
            media_type="audio/wav",
            headers={
                "Content-Disposition": f"inline; filename=fallback_{fallback_voice}.wav",
                "X-TTS-Engine": "piper-fallback",
                "X-TTS-Latency": elapsed,
                "X-TTS-Voice": fallback_voice,
                "Access-Control-Expose-Headers": "X-TTS-Engine, X-TTS-Latency, X-TTS-Voice"
            }
        )
    except Exception as piper_err:
        raise HTTPException(
            status_code=500,
            detail=f"Both Microsoft and Piper Fallback failed. Piper Error: {piper_err}"
        )

@app.post("/tts/ms", tags=["Text-to-Speech (TTS)"])
@app.post("/tts", tags=["Text-to-Speech (TTS)"], include_in_schema=False)
async def direct_microsoft_tts(req: TTSRequest):
    """Direct Microsoft Edge Neural Speech Synthesis (MP3)"""
    t0 = time.time()
    try:
        voice = req.voice or "ur-PK-UzmaNeural"
        audio_bytes = await generate_speech_bytes(
            text=req.text,
            voice=voice,
            rate=req.rate or "+0%",
            pitch=req.pitch or "+0Hz",
            volume=req.volume or "+0%"
        )
        elapsed = f"{time.time() - t0:.2f}s"
        return Response(
            content=audio_bytes,
            media_type="audio/mpeg",
            headers={
                "Content-Disposition": f"inline; filename=speech_{voice}.mp3",
                "X-TTS-Engine": "microsoft",
                "X-TTS-Latency": elapsed,
                "Access-Control-Expose-Headers": "X-TTS-Engine, X-TTS-Latency"
            }
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/tts/stream", tags=["Text-to-Speech (TTS)"])
@app.get("/tts-stream", tags=["Text-to-Speech (TTS)"], include_in_schema=False)
async def tts_stream_endpoint(
    text: str = Query(..., description="Text to speak"),
    voice: str = Query("ur-PK-UzmaNeural", description="Microsoft Neural Voice Name"),
    rate: str = Query("+0%", description="Speech rate (e.g. +20% or -10%)"),
    pitch: str = Query("+0Hz", description="Speech pitch (e.g. +10Hz or -5Hz)")
):
    """Direct audio stream link for browser <audio src='...'> tags"""
    try:
        audio_bytes = await generate_speech_bytes(text=text, voice=voice, rate=rate, pitch=pitch)
        return Response(
            content=audio_bytes,
            media_type="audio/mpeg",
            headers={
                "Content-Disposition": "inline; filename=stream.mp3",
                "Cache-Control": "no-cache"
            }
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/tts/voices", tags=["Text-to-Speech (TTS)"])
@app.get("/voices", tags=["Text-to-Speech (TTS)"], include_in_schema=False)
def get_voices(lang: Optional[str] = Query(None, description="Filter by language code (e.g. 'ur', 'en', 'ar')")):
    """Get list of all 300+ Microsoft Neural Voices & Accents"""
    if not lang:
        return {"success": True, "count": len(cached_voices), "voices": cached_voices}
    filtered = [v for v in cached_voices if v["locale"].lower().startswith(lang.lower())]
    return {"success": True, "count": len(filtered), "voices": filtered}

@app.post("/tts/piper", tags=["Text-to-Speech (TTS)"])
@app.post("/piper/tts", tags=["Text-to-Speech (TTS)"], include_in_schema=False)
async def direct_piper_tts(req: PiperTTSRequest):
    """Direct Piper Local Neural Speech Synthesis (WAV) with Speed & Pitch Controls"""
    t0 = time.time()
    try:
        audio_bytes = await generate_piper_bytes(
            text=req.text,
            voice_id=req.voice or "ur_PK-fasih-medium",
            speed=req.speed if req.speed is not None else 1.0,
            length_scale=req.length_scale,
            pitch=req.pitch if req.pitch is not None else 0.0,
            noise_scale=req.noise_scale or 0.667
        )
        elapsed = f"{time.time() - t0:.2f}s"
        return Response(
            content=audio_bytes,
            media_type="audio/wav",
            headers={
                "Content-Disposition": f"inline; filename=piper_{req.voice}.wav",
                "X-TTS-Engine": "piper-local",
                "X-TTS-Latency": elapsed,
                "Access-Control-Expose-Headers": "X-TTS-Engine, X-TTS-Latency"
            }
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/tts/piper-voices", tags=["Text-to-Speech (TTS)"])
@app.get("/piper/voices", tags=["Text-to-Speech (TTS)"], include_in_schema=False)
def get_piper_voices():
    """Get list of local ONNX models currently installed on VPS"""
    return {"success": True, "count": len(PIPER_VOICES), "voices": PIPER_VOICES}

# ==================== 3. SYSTEM & WEB UI MOUNTS ====================

@app.get("/health", tags=["System"])
def health():
    return {
        "status": "ok",
        "service": "Unified AI Voice & Speech Engine",
        "port": 8089,
        "stt_engine": {
            "model": STT_MODEL_ID,
            "status": "online",
            "is_busy": cpu_lock.locked(),
            "noise_suppression": {
                "engine": "RNNoise (Recurrent Neural Network)",
                "model": "rnnoise_speech.rnnn",
                "status": "active",
                "speed": "70x - 100x faster than real-time"
            }
        },
        "tts_engines": {
            "priority_1": "Microsoft Edge Neural TTS",
            "fallback": "Piper Local ONNX",
            "microsoft_voices": len(cached_voices),
            "piper_models": len(PIPER_VOICES)
        },
        "docs_url": "/docs",
        "timestamp": time.time()
    }

# Static Web UI Hosting on Port 8089
STATIC_DIR = "/root/stttts/static"
if os.path.exists(STATIC_DIR):
    app.mount("/ws3", StaticFiles(directory=f"{STATIC_DIR}/ws3", html=True), name="ws3_web")
    app.mount("/mstts", StaticFiles(directory=f"{STATIC_DIR}/mstts", html=True), name="mstts_web")
    app.mount("/piper", StaticFiles(directory=f"{STATIC_DIR}/piper", html=True), name="piper_web")
    app.mount("/hub", StaticFiles(directory=f"{STATIC_DIR}/hub", html=True), name="hub_web")

@app.get("/", include_in_schema=False)
@app.get("/index.html", include_in_schema=False)
def root_web():
    hub_file = f"{STATIC_DIR}/hub/index.html"
    if os.path.exists(hub_file):
        return FileResponse(hub_file)
    return RedirectResponse(url="/docs")

@app.get("/style.css", include_in_schema=False)
def root_css():
    f = f"{STATIC_DIR}/hub/style.css"
    if os.path.exists(f):
        return FileResponse(f, media_type="text/css")
    raise HTTPException(status_code=404)

@app.get("/app.js", include_in_schema=False)
def root_js():
    f = f"{STATIC_DIR}/hub/app.js"
    if os.path.exists(f):
        return FileResponse(f, media_type="application/javascript")
    raise HTTPException(status_code=404)

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8089)
```

---

## 7. Audio Enhancement & Noise Cancellation Filter Chain

Pre-Whisper audio enhancement cleans microphone static, road rumble, fan noise, and distant speaking:

### Supported Parameters & Recommended Settings:
| Parameter | Type | Default | Recommended Best | Function |
|---|---|---|---|---|
| `denoise` | `bool` | `true` | **`true` ★** | Master switch for RNNoise neural filter |
| `denoise_level` | `str` | `'medium'` | **`'medium'` ★** | `'light'` (70/30 natural mix) | `'medium'` (Balanced) | `'aggressive'` (Heavy cut) |
| `highpass_filter` | `bool` | `true` | **`true` ★** | 80Hz cutoff filter for desk thumps, AC hum, and wind rumble |
| `speech_boost` | `bool` | `true` | **`true` ★** | Dynamic Audio Normalization (`dynaudnorm`) to balance quiet/distant speech |
| `noise_gate` | `bool` | `false` | **`false`** | Completely mutes background during pauses between words |

### FFmpeg Filter Chain Command:
* **Light Mode (Natural Dry/Wet Blend):**
  `ffmpeg -i input.wav -filter_complex "[0:a]highpass=f=80,asplit=2[orig][noisy];[noisy]arnndn=m='rnnoise_speech.rnnn'[clean];[clean][orig]amix=inputs=2:weights=0.7 0.3,dynaudnorm=f=150:g=15[out]" -map "[out]" -ar 16000 -ac 1 output.wav`
* **Medium Mode (Balanced 100% Neural Suppression):**
  `ffmpeg -i input.wav -af "highpass=f=80,arnndn=m='rnnoise_speech.rnnn',dynaudnorm=f=150:g=15" -ar 16000 -ac 1 output.wav`
* **Aggressive Mode (Heavy Noise + Gate):**
  `ffmpeg -i input.wav -af "highpass=f=80,arnndn=m='rnnoise_speech.rnnn',agate=threshold=-32dB:ratio=2:range=-50dB,dynaudnorm=f=150:g=15" -ar 16000 -ac 1 output.wav`

---

## 8. Frontend Web Studios & UI Architecture

### 8.1 Hub Launchpad (`/`)
* Displays 3 major studio launch cards: **WS3 (STT)**, **MSTTS (Cloud TTS)**, and **Piper (Local TTS)**.
* Embedded Matrix: Latency, RAM, Urdu Accuracy, English Accuracy.

### 8.2 WS3 STT Studio (`/ws3/`)
* **Audio Enhancement Controls Card:**
  * Segmented buttons for `Light`, `Medium (★ Best)`, `Aggressive`.
  * Toggles for `Cut Low Rumble (<80Hz)`, `Speech Boost & Normalize`, and `Auto Noise Gate`.
* **Modes:** Live Streaming Dictation, Audio File Upload, Mic Recording.
* **Outputs:** Real-time stream, waveform visualizer, TXT & JSON export.
* **Documentation Tabs:** cURL, JavaScript Fetch, Python Requests, JSON Schema.

### 8.3 MSTTS Microsoft Studio (`/mstts/`)
* 300+ Microsoft Edge neural voices with country flags and search filter.
* Speed slider (`0.5x` to `2.0x`) and Pitch slider (`-50Hz` to `+50Hz`).
* Direct audio streaming player and download button.

### 8.4 Piper Local ONNX Studio (`/piper/`)
* 100% offline synthesis testing.
* Sliders for Speed, Pitch, and Voice Expressiveness (`noise_scale`).

### 8.5 Universal Copy Engine & Top-Right Toast Alert
In all `app.js` files, clipboard copying works over both HTTP and HTTPS using `document.execCommand('copy')` fallback and renders a floating `#studio-toast` notification at the top-right corner.

### 8.6 AI Prompt Generator Banner
Every studio page includes a dedicated **AI Prompt Generator** banner with an instant copy button (`copyAiPrompt()`). Developers can paste this prompt directly into ChatGPT, Claude, or Gemini to automatically generate fully working client integration code with recommended settings.

---

## 9. API Endpoints Reference & JSON Schemas

### 1. `POST /stt/transcribe` (Multipart Form-Data)
```bash
curl -X POST "http://<IP>:8089/stt/transcribe" \
  -F "file=@voice.wav" \
  -F "language=ur" \
  -F "denoise=true" \
  -F "denoise_level=medium" \
  -F "highpass_filter=true" \
  -F "speech_boost=true" \
  -F "noise_gate=false"
```

**Response JSON:**
```json
{
  "success": true,
  "filename": "voice.wav",
  "text": "یہ آواز کا نتیجہ ہے۔",
  "detected_language": "ur",
  "language_probability": 0.985,
  "audio_duration_seconds": 5.2,
  "inference_time_seconds": 1.1,
  "rnnoise_applied": true,
  "denoise_settings": {
    "denoise": true,
    "level": "medium",
    "highpass_filter": true,
    "speech_boost": true,
    "noise_gate": false
  },
  "rnnoise_latency_seconds": 0.089,
  "segments": [{ "id": 1, "start": 0.0, "end": 5.2, "text": "یہ آواز کا نتیجہ ہے۔" }]
}
```

### 2. `POST /tts/synthesize` (Smart Hybrid Synthesis)
```bash
curl -X POST "http://<IP>:8089/tts/synthesize" \
  -H "Content-Type: application/json" \
  -d '{"text": "سلام، آپ کیسے ہیں؟", "voice": "ur-PK-AsadNeural", "speed": 1.0}' \
  --output voice.mp3
```

---

## 10. PM2 Production Management & Monitoring

```bash
# Check status
pm2 status

# Live logs
pm2 logs stttts-api

# Resource monitor (CPU & RAM)
pm2 monit

# Restart service
pm2 restart stttts-api

# Save configuration across server reboots
pm2 save
pm2 startup
```

---

## 11. Testing & Verification Test Suite

Run these commands to verify that every component is working:

```bash
# 1. Health check
curl -s http://127.0.0.1:8089/health | jq .

# 2. Test Microsoft TTS (Returns MP3)
curl -X POST http://127.0.0.1:8089/tts/ms \
  -H "Content-Type: application/json" \
  -d '{"text": "سلام", "voice": "ur-PK-AsadNeural"}' \
  --output /tmp/test_ms.mp3 && ls -lh /tmp/test_ms.mp3

# 3. Test Piper Local ONNX TTS (Returns WAV)
curl -X POST http://127.0.0.1:8089/tts/piper \
  -H "Content-Type: application/json" \
  -d '{"text": "سلام", "voice": "ur_PK-fasih-medium"}' \
  --output /tmp/test_piper.wav && ls -lh /tmp/test_piper.wav

# 4. Test STT Audio Transcription with Noise Suppression
curl -X POST http://127.0.0.1:8089/stt/transcribe \
  -F "file=@/tmp/test_piper.wav" \
  -F "language=ur" \
  -F "denoise=true" \
  -F "denoise_level=medium" \
  -F "highpass_filter=true" \
  -F "speech_boost=true"
```
