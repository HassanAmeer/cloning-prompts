# 🤖 AI Integration Cheatsheet: Unified STT & TTS API
> **Copy-paste this file directly into ChatGPT, Claude, Cursor, Antigravity, or your AI IDE to implement Speech-to-Text (STT) and Text-to-Speech (TTS) into your project in minutes.**

---

## 🧭 Instructions for AI Coding Assistants
When implementing voice features in this codebase:
1. **Base URL:** Always use `http://145.223.34.142:8089` (Hostinger VPS Unified API).
2. **CORS:** Enabled for all origins (`*`) — works in browsers, React, Next.js, React Native, Node.js, and Chrome/WhatsApp Extensions.
3. **STT Recommendation:** Use `POST /stt/transcribe` with `denoise=true`, `denoise_level=medium`, `highpass_filter=true`, and `speech_boost=true`.
4. **TTS Recommendation:** Use `POST /tts/synthesize` (Smart Hybrid router that prioritizes Microsoft Edge Studio and auto-falls back to Piper Local ONNX if cloud is unreachable).

---

## 📌 Quick API Reference Table

| Feature | HTTP Method & Route | Request Type | Expected Output |
|---|---|---|---|
| **STT: Full Audio Transcribe** | `POST /stt/transcribe` | `multipart/form-data` | `JSON` with text, confidence, latency, denoise info |
| **STT: Live Chunk Dictation** | `POST /stt/transcribe-live` | `multipart/form-data` | `JSON` with interim text (~0.2s latency) |
| **TTS: Smart Hybrid Synthesis** | `POST /tts/synthesize` | `application/json` | Binary `audio/mpeg` (MS) or `audio/wav` (Piper) |
| **TTS: Direct Microsoft Edge** | `POST /tts/ms` | `application/json` | Binary `audio/mpeg` (300+ Studio Voices) |
| **TTS: Direct Piper Local** | `POST /tts/piper` | `application/json` | Binary `audio/wav` (100% Offline ONNX) |
| **TTS: Microsoft Voices List** | `GET /tts/voices` | None | `JSON` array of 300+ voices with locales |
| **TTS: Piper Voices List** | `GET /tts/piper-voices` | None | `JSON` array of locally installed ONNX models |
| **System Health Check** | `GET /health` | None | `JSON` engine & memory status |
| **Interactive Swagger Docs** | `GET /docs` | Browser | OpenAPI interactive documentation |

---

## 🎙️ 1. Speech-to-Text (STT) API Specification

### Endpoint: `POST http://145.223.34.142:8089/stt/transcribe`
Converts voice recordings, audio notes, and audio files into high-accuracy Urdu or English text.

#### Request Parameters (`multipart/form-data`):
| Field | Type | Required | Recommended Value | Description |
|---|---|---|---|---|
| `file` | `File` / `Blob` | **Yes** | Any audio file | Supports `WAV`, `MP3`, `M4A`, `OGG`, `WebM` |
| `language` | `string` | Optional | `'ur'` or `'en'` | Omit or pass `null` for automatic language detection |
| `task` | `string` | Optional | `'transcribe'` | `'transcribe'` (same language) or `'translate'` (translate to English) |
| `beam_size` | `integer` | Optional | `1` | `1` = fastest (~2.5s), `5` = highest accuracy |
| `denoise` | `boolean` | Optional | **`true` ★** | Enables pre-Whisper AI noise suppression |
| `denoise_level` | `string` | Optional | **`'medium'` ★** | `'light'` (70/30 blend), `'medium'` (balanced), `'aggressive'` (heavy cut) |
| `highpass_filter`| `boolean` | Optional | **`true` ★** | Cuts rumble, AC hum, and mic bumps below 80Hz |
| `speech_boost` | `boolean` | Optional | **`true` ★** | Dynamic normalization (`dynaudnorm`) to amplify whispery/distant voices |
| `noise_gate` | `boolean` | Optional | `false` | Completely mutes background during speech pauses |

#### Response JSON:
```json
{
  "success": true,
  "filename": "voice_note.ogg",
  "text": "یہ آواز کی درست اردو ٹرانسکرپشن ہے۔",
  "detected_language": "ur",
  "language_probability": 0.985,
  "audio_duration_seconds": 4.5,
  "inference_time_seconds": 1.2,
  "rnnoise_applied": true,
  "denoise_settings": {
    "denoise": true,
    "level": "medium",
    "highpass_filter": true,
    "speech_boost": true,
    "noise_gate": false
  },
  "rnnoise_latency_seconds": 0.089,
  "segments": [
    { "id": 1, "start": 0.0, "end": 4.5, "text": "یہ آواز کی درست اردو ٹرانسکرپشن ہے۔" }
  ]
}
```

---

## 🔊 2. Text-to-Speech (TTS) API Specification

### Endpoint: `POST http://145.223.34.142:8089/tts/synthesize`
Generates natural-sounding speech from text using smart hybrid failover.

#### Request JSON Body (`application/json`):
```json
{
  "text": "السلام علیکم، آپ کا پارسل روانہ کر دیا گیا ہے۔",
  "voice": "ur-PK-AsadNeural",
  "speed": 1.0,
  "pitch": 0.0
}
```

#### Field Specifications:
* **`text`** *(string, required)*: Text to speak (supports Urdu, English, Arabic, etc.).
* **`voice`** *(string, optional, default: `'ur-PK-AsadNeural'`)*:
  * **Urdu Pakistani Voices:** `'ur-PK-AsadNeural'` (Male), `'ur-PK-UzmaNeural'` (Female).
  * **English US Voices:** `'en-US-JennyNeural'` (Female), `'en-US-GuyNeural'` (Male), `'en-US-AriaNeural'`.
  * **English UK Voices:** `'en-GB-SoniaNeural'`, `'en-GB-RyanNeural'`.
  * **Urdu Local Offline (Piper):** `'ur_PK-fasih-medium'`.
* **`speed`** *(float, default: `1.0`)*: Speech rate multiplier (`0.5` slow to `2.0` fast).
* **`pitch`** *(float, default: `0.0`)*: Pitch adjustment in Hz (`-50.0` deep to `+50.0` high).

#### Response:
* Returns **raw binary audio data** (`audio/mpeg` or `audio/wav`).
* Response Headers:
  * `X-TTS-Engine`: `'microsoft'` or `'piper-fallback'`
  * `X-TTS-Latency`: e.g. `'0.85s'`

---

## 📦 3. Ready-to-Use JavaScript / TypeScript SDK (`VoiceAIService.js`)

Copy this self-contained service into your project (e.g. `src/services/VoiceAIService.js`):

```javascript
/**
 * VoiceAIService.js - Universal STT & TTS Client
 * Works in React, Next.js, Vue, Node.js, and Chrome/WhatsApp Web Extensions
 */
const BASE_URL = 'http://145.223.34.142:8089';

export const VoiceAIService = {
  /**
   * Transcribe an Audio File or Blob to Text
   * @param {Blob|File} audioFileOrBlob - Audio recording
   * @param {Object} options - Customization options
   * @returns {Promise<string>} Transcribed text
   */
  async transcribe(audioFileOrBlob, options = {}) {
    const formData = new FormData();
    formData.append('file', audioFileOrBlob, options.filename || 'recording.wav');
    
    if (options.language) formData.append('language', options.language); // 'ur' or 'en'
    formData.append('task', options.task || 'transcribe');
    
    // 🎙️ Best Recommended Noise Cancellation Settings
    formData.append('denoise', options.denoise !== false ? 'true' : 'false');
    formData.append('denoise_level', options.denoise_level || 'medium');
    formData.append('highpass_filter', options.highpass_filter !== false ? 'true' : 'false');
    formData.append('speech_boost', options.speech_boost !== false ? 'true' : 'false');
    formData.append('noise_gate', options.noise_gate ? 'true' : 'false');

    const res = await fetch(`${BASE_URL}/stt/transcribe`, {
      method: 'POST',
      body: formData,
    });

    if (!res.ok) {
      const err = await res.json().catch(() => null);
      throw new Error(err?.detail || `STT Error: HTTP ${res.status}`);
    }

    const data = await res.json();
    return data.text;
  },

  /**
   * Synthesize Speech from Text and Play Immediately in Browser
   * @param {string} text - Text to speak
   * @param {Object} options - Voice options
   * @returns {Promise<HTMLAudioElement>}
   */
  async speak(text, options = {}) {
    const audioBlob = await this.synthesizeToBlob(text, options);
    const audioUrl = URL.createObjectURL(audioBlob);
    const audio = new Audio(audioUrl);
    await audio.play();
    return audio;
  },

  /**
   * Synthesize Speech and Return Audio Blob (MP3/WAV)
   * @param {string} text - Text to synthesize
   * @param {Object} options - { voice, speed, pitch }
   * @returns {Promise<Blob>}
   */
  async synthesizeToBlob(text, options = {}) {
    const res = await fetch(`${BASE_URL}/tts/synthesize`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        text,
        voice: options.voice || 'ur-PK-AsadNeural',
        speed: options.speed || 1.0,
        pitch: options.pitch || 0.0,
      }),
    });

    if (!res.ok) {
      throw new Error(`TTS Error: HTTP ${res.status}`);
    }

    return await res.blob();
  },

  /**
   * Get List of Available Microsoft Studio Voices
   * @param {string} langPrefix - e.g. 'ur' or 'en'
   */
  async getVoices(langPrefix = '') {
    const url = langPrefix ? `${BASE_URL}/tts/voices?lang=${langPrefix}` : `${BASE_URL}/tts/voices`;
    const res = await fetch(url);
    const data = await res.json();
    return data.voices || [];
  }
};
```

---

## 🐍 4. Ready-to-Use Python Client (`voice_client.py`)

For backend bots (WhatsApp bots, Telegram bots, Django/FastAPI microservices):

```python
import requests

BASE_URL = "http://145.223.34.142:8089"

def transcribe_audio_file(file_path: str, language: str = "ur") -> str:
    """Transcribes an audio file with RNNoise noise reduction."""
    url = f"{BASE_URL}/stt/transcribe"
    with open(file_path, "rb") as f:
        files = {"file": (file_path, f, "audio/wav")}
        data = {
            "language": language,
            "task": "transcribe",
            "denoise": "true",
            "denoise_level": "medium",
            "highpass_filter": "true",
            "speech_boost": "true",
            "noise_gate": "false"
        }
        resp = requests.post(url, files=files, data=data, timeout=30)
        resp.raise_for_status()
        return resp.json().get("text", "")

def text_to_speech(text: str, output_path: str = "output.mp3", voice: str = "ur-PK-AsadNeural", speed: float = 1.0):
    """Generates MP3/WAV speech audio from text using Smart Hybrid Synthesis."""
    url = f"{BASE_URL}/tts/synthesize"
    payload = {
        "text": text,
        "voice": voice,
        "speed": speed,
        "pitch": 0.0
    }
    resp = requests.post(url, json=payload, timeout=30)
    resp.raise_for_status()
    with open(output_path, "wb") as f:
        f.write(resp.content)
    print(f"--> Saved audio to {output_path} (Engine: {resp.headers.get('X-TTS-Engine')})")
    return output_path

# Quick Test:
if __name__ == "__main__":
    print("Testing TTS...")
    text_to_speech("السلام علیکم، آپ کا آرڈر تیار ہے۔", "test_order.mp3")
```

---

## 📱 5. WhatsApp Extension / Chat Integration Snippet

If you are integrating into a **WhatsApp Extension / Chrome Extension** or chat application:

### A. Transcribe Incoming WhatsApp Voice Note (`.ogg` / `.opus`):
```javascript
// Example: Converting WhatsApp voice note blob to Urdu text
async function handleWhatsAppVoiceNote(voiceNoteBlob) {
  try {
    const text = await VoiceAIService.transcribe(voiceNoteBlob, {
      language: 'ur',
      filename: 'whatsapp_voice.ogg',
      denoise: true,
      denoise_level: 'medium', // Cleans fan & street noise from phone mics
      speech_boost: true
    });
    console.log('WhatsApp Transcribed Message:', text);
    return text;
  } catch (err) {
    console.error('Failed to transcribe voice note:', err);
  }
}
```

### B. Reply with Natural Voice Note:
```javascript
// Example: Sending or playing speech reply
async function sendVoiceReply(replyText) {
  const audioBlob = await VoiceAIService.synthesizeToBlob(replyText, {
    voice: 'ur-PK-AsadNeural', // Studio realistic Urdu male voice
    speed: 1.05
  });
  
  // Can be attached to formData to send via WhatsApp Web API or played directly:
  const audioUrl = URL.createObjectURL(audioBlob);
  const audio = new Audio(audioUrl);
  audio.play();
}
```

---

## ⚡ 6. Live Streaming Dictation (Sub-Second Chunks)

To transcribe speech **while the user is talking** (real-time live dictation):

```javascript
// Collect short 2-3 second MediaRecorder blobs and send to transcribe-live:
async function sendLiveSpeechChunk(blobChunk, lang = 'ur') {
  const formData = new FormData();
  formData.append('file', blobChunk, 'chunk.wav');
  formData.append('language', lang);
  formData.append('denoise', 'true');
  formData.append('denoise_level', 'medium');

  const res = await fetch('http://145.223.34.142:8089/stt/transcribe-live', {
    method: 'POST',
    body: formData
  });

  if (res.ok) {
    const data = await res.json();
    if (data.text) {
      console.log('Live word:', data.text);
      return data.text;
    }
  }
  return '';
}
```

---

## 🛠️ 7. Troubleshooting & Common Pitfalls

| Issue | Cause | Fix |
|---|---|---|
| `TypeError: Failed to fetch` | HTTP to HTTPS mixed content blocking | If your frontend is on HTTPS (`https://...`), configure an SSL reverse proxy (e.g. `https://ai.yourdomain.com`) or run client on localhost/plain HTTP. |
| Microphone permission denied | Browser blocks `getUserMedia` on plain HTTP | Test on `localhost` or access via Chrome flag `--unsafely-treat-insecure-origin-as-secure`. |
| Voice sounds muffled in STT | Denoise level too aggressive | Use `denoise_level: 'medium'` (Recommended) or `'light'` (70% blend) for quiet rooms. |
| Whisper hallucinations on silence | Empty audio sent | The API automatically has `vad_filter=True` and `min_silence_duration_ms=400` enabled. |

---

## 🎯 Summary Prompt to Give Your AI:
> *"I have an AI Voice API running at `http://145.223.34.142:8089`. It has STT (`POST /stt/transcribe`) and TTS (`POST /tts/synthesize`). Please read `instant-setup-stt-tts.md` and integrate `VoiceAIService` into my project so users can record/transcribe voice and play spoken text."*
