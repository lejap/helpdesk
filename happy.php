<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SYSTEM CRITICAL ERROR</title>
    <style>
        body {
            background-color: black;
            color: #0f0; /* Matrix Green */
            font-family: 'Courier New', Courier, monospace;
            overflow: hidden;
            margin: 0;
            padding: 20px;
        }
        .container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 80%;
        }
        h1 {
            font-size: 3rem;
            color: red;
            text-transform: uppercase;
            animation: blink 0.5s infinite;
        }
        p {
            font-size: 1.2rem;
        }
        .code-rain {
            color: #0f0;
            font-size: 14px;
            text-align: left;
            opacity: 0.7;
            margin-top: 20px;
        }
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0; }
            100% { opacity: 1; }
        }
        
        /* Simulated Glitch Effect */
        .glitch {
            position: relative;
        }
        .glitch::before, .glitch::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .glitch::before {
            left: 2px;
            text-shadow: -1px 0 red;
            clip: rect(24px, 550px, 90px, 0);
            animation: glitch-anim-1 2.5s infinite linear alternate-reverse;
        }
        .glitch::after {
            left: -2px;
            text-shadow: -1px 0 blue;
            clip: rect(85px, 550px, 140px, 0);
            animation: glitch-anim-2 3s infinite linear alternate-reverse;
        }
        @keyframes glitch-anim-1 {
            0% { clip: rect(20px, 9999px, 80px, 0); }
            100% { clip: rect(120px, 9999px, 180px, 0); }
        }
        @keyframes glitch-anim-2 {
            0% { clip: rect(120px, 9999px, 180px, 0); }
            100% { clip: rect(10px, 9999px, 60px, 0); }
        }
        
        .hidden-btn {
            margin-top: 50px;
            background: transparent;
            border: 1px solid #333;
            color: #333;
            cursor: pointer;
        }
        .hidden-btn:hover {
            color: #444;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="glitch" data-text="SYSTEM CORRUPTED">SYSTEM CORRUPTED</h1>
        <p>CRITICAL DATABASE FAILURE DETECTED</p>
        <p>DELETING SECTORS 0x0001 TO 0xFFFF...</p>
        <br>
        <div id="loading">[=================>........] 64%</div>
        <br>
        <div class="code-rain">
            ERROR: User data not found.<br>
            ERROR: Ticket table dropped.<br>
            WARNING: Unauthorized access detected from IP 192.168.1.X<br>
            INITIATING SYSTEM PURGE...
        </div>

       
    </div>

    <script>
        // Simple script to make text scroll and look scary
        const terminal = document.querySelector('.code-rain');
        const loading = document.getElementById('loading');
        let percent = 64;

        setInterval(() => {
            if(percent < 99) {
                percent++;
                loading.innerText = "[=================>" + ".".repeat(100-percent) + "] " + percent + "%";
            } else {
                loading.innerText = "SYSTEM DESTROYED. HAVE A NICE DAY.";
                loading.style.color = "red";
            }
            
            // Add random garbage text
            const line = document.createElement('div');
            line.innerText = "0x" + Math.floor(Math.random()*16777215).toString(16) + " SEGMENTATION FAULT";
            terminal.appendChild(line);
            
            if(terminal.childElementCount > 10) terminal.firstChild.remove();
        }, 100);
    </script>
</body>
</html>