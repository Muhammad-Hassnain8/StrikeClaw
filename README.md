# StrikeClaw - An Advanced Keylogger

<p align="center">
  <img src="https://github.com/user-attachments/assets/48b47d5c-b082-4382-87db-d52744872aff" alt="StrikeClaw Banner" width="800"/>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-2.0-gold?style=for-the-badge&labelColor=1a1e26&color=d4a017"/>
  <img src="https://img.shields.io/badge/platform-windows%20%7C%20linux%20%7C%20macos-blue?style=for-the-badge&labelColor=1a1e26&color=6ba6ff"/>
  <img src="https://img.shields.io/badge/python-3.6%2B-success?style=for-the-badge&labelColor=1a1e26&color=00ff9d"/>
  <img src="https://img.shields.io/badge/php-7%2B-purple?style=for-the-badge&labelColor=1a1e26&color=b794f4"/>
</p>

<p align="center">
  <b>⚡ Precision Monitoring · 🎯 Real-time Intelligence · 🦅 Falcon-Eye Precision</b>
</p>

---

## 📋 Overview

**StrikeClaw** is a sophisticated monitoring system designed for authorized security testing and educational purposes. Named after the falcon's deadly precision strike, it combines a lightweight Python agent with a powerful PHP-based command center.

> ⚠️ **IMPORTANT**: This tool is for **AUTHORIZED TESTING ONLY**. Unauthorized use violates privacy laws and ethical guidelines. Always obtain explicit consent before deployment.

---

## ✨ Key Features

### 🎯 **Agent Capabilities**
- **Cross-platform support** (Windows, Linux, macOS)
- **Application context tracking** - Knows which app is active
- **Mouse click monitoring** with coordinates
- **Offline buffering** - Stores data when disconnected
- **Auto-reconnect** - Sends data when connection restored
- **Persistent agent ID** - Survives reboots
- **Low resource footprint** - Runs silently in background

### 🦅 **Command Center**
- **Real-time dashboard** with reactive updates
- **Multiple target management**
- **Application usage analytics**
- **Activity timeline visualization**
- **Data export** (download/clear/copy)
- **Live status indicators**
- **Configurable refresh rates**

---

## 📸 Screenshots

<p align="center">
  <img src="[https://via.placeholder.com/800x450?text=Dashboard+Overview](https://github.com/user-attachments/assets/3a94fcad-d922-4161-921c-dc2387b9dfa8)" alt="Dashboard Overview" width="800"/>
  <br/>
  <em>Main Dashboard - Target Overview</em>
</p>

<p align="center">
  <img src="https://github.com/user-attachments/assets/eb3f00fb-09ed-497b-82a3-871a55e72f84" alt="Application Analytics" width="800"/>
  <br/>
  <em>Application Usage Analysis</em>
</p>

---

## 🚀 Quick Start

### 📦 Prerequisites

#### Client Requirements
```bash
# Python 3.6 or higher
python --version

# Required packages
pip install requests pynput

# Platform-specific (optional - for window titles)
# Windows
pip install pywin32
# Linux
pip install ewmh python-xlib
# macOS
pip install pyobjc-framework-Cocoa
```

#### Server Requirements
- PHP 7.0 or higher
- Web server (Apache/Nginx) or PHP built-in server
- Write permissions in the script directory

### 📥 Installation

#### 1. Clone or Download
```bash
git clone https://github.com/yourusername/strikeclaw.git
cd strikeclaw
```

#### 2. Server Setup
```bash
# Upload to your web server
cp -r server/* /var/www/html/strikeclaw/

# Set permissions
chmod 755 /var/www/html/strikeclaw/logs
```

#### 3. Client Configuration
Edit `strikeclaw_client.py`:
```python
SERVER_URL = "http://your-server.com/strikeclaw/log.php"  # Change this
```

#### 4. Run the Agent
```bash
# Make executable (Linux/macOS)
chmod +x strikeclaw_client.py

# Run in foreground
python3 strikeclaw_client.py

# Run in background (Linux/macOS)
nohup python3 strikeclaw_client.py > /dev/null 2>&1 &

# Run in background (Windows)
pythonw.exe strikeclaw_client.py
```

---

## 🎮 Usage Guide

### 🖥️ **Command Center Dashboard**

Access the dashboard:
```
http://your-server.com/strikeclaw/
```

#### Dashboard Controls

| Control | Description |
|---------|-------------|
| 🦅 **Sync Now** | Manual data refresh |
| ⏸️ **Pause Falcon Eye** | Toggle auto-refresh |
| **Refresh Interval** | 5s - 5min options |
| ⬇️ **Extract** | Download intelligence |
| 🗑️ **Purge** | Clear all data |
| 📋 **Copy** | Copy to clipboard |

#### Tabs Overview

| Tab | Purpose |
|-----|---------|
| 📝 **Raw Data** | Unfiltered keystroke/mouse logs |
| 🪟 **Applications** | App usage statistics |
| 📊 **Analytics** | Activity metrics |
| ⏰ **Timeline** | Hourly activity distribution |

### 📊 **Understanding the Data**

#### Log Format
```
[KEY] 2024-01-15 14:30:25 | Window: Google Chrome | Key: hello
[MOUSE] 2024-01-15 14:30:30 | Window: Visual Studio Code | Click at (500, 300) with left
```

#### Status Indicators
- 🟢 **Green dot** - Target in sight (active within 5 min)
- 🔴 **Red dot** - Target out of range (inactive)

---

## 🏗️ Architecture

```
┌─────────────────┐     ┌──────────────┐     ┌─────────────────┐
│   StrikeClaw    │────▶│    Server     │────▶│    Command      │
│     Agent       │     │   (PHP)       │     │   Center        │
│  (Python)       │◀────│  log.php      │     │  (Dashboard)    │
└─────────────────┘     └──────────────┘     └─────────────────┘
         │                       │                      │
         ▼                       ▼                      ▼
   Local Buffer           Flat File Storage        Real-time UI
   (~/.strikeclaw_*)      (logs/client_id/)        (Reactive AJAX)
```

### Data Flow
1. **Agent** captures events → stores locally
2. **Agent** checks connectivity → sends to server
3. **Server** receives → appends to client file
4. **Dashboard** reads files → displays in UI
5. **Auto-refresh** updates every N seconds

---

## ⚙️ Configuration

### Agent Configuration (`strikeclaw_client.py`)

```python
SERVER_URL = "http://your-server.com/log.php"   # Server endpoint
LOG_FILE = "~/.strikeclaw_data.log"             # Local storage
SEND_INTERVAL = 60                               # Send frequency (seconds)
DEBUG = True                                      # Console output
```

### Server Configuration (`index.php`)

```php
$baseDir = __DIR__ . '/logs';      # Storage directory
$refreshRate = 10;                   # Dashboard refresh (seconds)
```

---

## 🛡️ Security Notes

### ⚠️ **Legal & Ethical Considerations**
- **This tool is for AUTHORIZED TESTING ONLY**
- **Always obtain written consent** before deployment
- **Respect privacy laws** in your jurisdiction
- **Use on your own devices** for learning

### 🔒 **Best Practices**
- Use HTTPS for all communications
- Implement authentication on dashboard
- Restrict access by IP address
- Regularly rotate log files
- Encrypt sensitive data at rest

---

## ❓ Troubleshooting

### Common Issues

**Q: Agent not capturing window titles?**
- Install platform-specific dependencies
- Run with appropriate permissions (sudo/administrator)

**Q: No data showing in dashboard?**
- Check server URL configuration
- Verify file permissions on server
- Check browser console for errors

**Q: Auto-refresh not working?**
- Ensure JavaScript is enabled
- Check browser console for AJAX errors
- Verify `ajax_handler.php` exists

**Q: Connection refused errors?**
- Verify server is running
- Check firewall settings
- Confirm URL is correct

---

## 📁 File Structure

```
strikeclaw/
├── client/
│   ├── strikeclaw_client.py    # Main agent
│   └── requirements.txt         # Dependencies
├── server/
│   ├── index.php               # Dashboard
│   ├── log.php                 # Receiver endpoint
│   ├── ajax_handler.php        # AJAX handler
│   ├── clear_logs.php          # Clear utility
│   ├── download_logs.php       # Download utility
│   └── logs/                   # Data storage
│       └── [client_id]/
│           └── log.txt
└── README.md                    # This file
```

---

## 📊 Performance

- **Agent CPU usage**: < 1% on modern hardware
- **Memory footprint**: ~30-50 MB
- **Storage**: ~1 KB per 100 events
- **Network**: Compressed, batched transfers
- **Scalability**: Handles 1000+ clients efficiently

---

## 🔄 Updates & Maintenance

### Updating the Agent
```bash
# Pull latest changes
git pull origin main

# Reinstall dependencies
pip install -r requirements.txt --upgrade
```

### Log Rotation
Logs are automatically appended. Implement external rotation:
```bash
# Example cron job for log rotation
0 0 * * * find /path/to/logs -name "*.txt" -size +100M -exec gzip {} \;
```

---

## 🤝 Contributing

While this is primarily a personal project, suggestions and improvements are welcome:

1. Fork the repository
2. Create a feature branch
3. Test thoroughly
4. Submit a pull request

---

## 📜 Disclaimer

**THIS SOFTWARE IS PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND.** 

The authors and contributors are not responsible for any misuse or damage caused by this tool. Users are solely responsible for complying with all applicable laws and regulations in their jurisdiction.

Unauthorized monitoring of computer systems is illegal and unethical. This tool should only be used:
- On systems you own
- With explicit written permission
- For legitimate security testing
- For educational purposes

---

## ⭐ Acknowledgments

- Inspired by the precision of falcons in nature
- Built with Python's pynput library
- Dashboard powered by PHP and modern JavaScript

---

<p align="center">
  <img src="https://via.placeholder.com/200x100?text=StrikeClaw+Logo" alt="StrikeClaw" width="200"/>
</p>

<p align="center">
  <b>Strike with Precision · Monitor with Purpose</b>
</p>

<p align="center">
  <sub>© 2024 StrikeClaw Project. All rights reserved.</sub>
</p>

---

*Last updated: January 2024*
