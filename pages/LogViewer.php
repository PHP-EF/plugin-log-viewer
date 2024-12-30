<?php
// Define the log files to monitor
$logFiles = ["packer.txt", "Packer_Powershell_log.txt", "git_pull.txt", "php.error.log"];

// Function to safely read log file content
function getLogContent($filename) {
    $logPath = __DIR__ . "inc/logs/" . basename($filename);
    if (file_exists($logPath)) {
        return htmlspecialchars(file_get_contents($logPath));
    }
    return "Log file not found";
}

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
    $file = isset($_GET['file']) ? $_GET['file'] : '';
    if (in_array($file, $logFiles)) {
        echo getLogContent($file);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .log-container {
            margin: 20px auto;
            max-width: 80%;
        }
        pre {
            background-color: #333;
            color: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 400px;
            overflow-y: scroll;
        }
        h1 {
            text-align: center;
        }
    </style>
    <script>
        function refreshLogs() {
            const logFiles = <?php echo json_encode($logFiles); ?>;
            logFiles.forEach(file => {
                fetch(`?action=refresh&file=${encodeURIComponent(file)}`)
                    .then(response => response.text())
                    .then(data => {
                        const logElement = document.getElementById(file);
                        logElement.textContent = data;
                        logElement.scrollTop = logElement.scrollHeight;
                    })
                    .catch(error => console.error("Error loading log file:", error));
            });
        }
        setInterval(refreshLogs, 1000);
        window.onload = refreshLogs;
    </script>
</head>
<body>
    <h1>Log Viewer</h1>
    <?php foreach ($logFiles as $file): ?>
    <div class="log-container">
        <h2><?php echo htmlspecialchars(str_replace('_', ' ', pathinfo($file, PATHINFO_FILENAME))); ?></h2>
        <pre id="<?php echo htmlspecialchars($file); ?>"><?php echo getLogContent($file); ?></pre>
    </div>
    <?php endforeach; ?>
</body>
</html>
