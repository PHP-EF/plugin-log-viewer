<?php
// Define the log files to monitor
$logFiles = ["php.error.log","packer.txt", "Packer_Powershell_log.txt", "git_pull.txt"]; //,"packer.txt", "Packer_Powershell_log.txt", "git_pull.txt",

// Define log paths to check
$logPaths = [
    "/var/www/html/inc/logs/",
    "/mnt/logs/"
];

// Function to safely read log file content
function getLogContent($filename) {
    global $logPaths;
    
    foreach ($logPaths as $basePath) {
        $logPath = $basePath . basename($filename);
        // Debug file path and existence
        error_log("Checking path: " . $logPath . " - Exists: " . (file_exists($logPath) ? 'yes' : 'no'));
        if (file_exists($logPath)) {
            $content = @file_get_contents($logPath);
            if ($content === false) {
                error_log("Failed to read file: " . $logPath . " - Error: " . error_get_last()['message']);
                continue;
            }
            return htmlspecialchars($content);
        }
    }
    
    // List all files in /mnt/logs for debugging
    if (is_dir("/mnt/logs")) {
        $files = scandir("/mnt/logs");
        error_log("Files in /mnt/logs: " . print_r($files, true));
    } else {
        error_log("/mnt/logs is not a directory or not accessible");
    }
    
    return "Log file not found in any of the configured paths. Checked: " . implode(", ", array_map(function($path) use ($filename) {
        return $path . basename($filename);
    }, $logPaths));
}

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
    header('Content-Type: application/json');
    $file = isset($_GET['file']) ? $_GET['file'] : '';
    if (in_array($file, $logFiles)) {
        echo json_encode([
            'status' => 'success',
            'content' => getLogContent($file)
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid file specified'
        ]);
    }
    exit;
}
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Log Viewer</h3>
                </div>
                <div class="card-body">
                    <?php foreach ($logFiles as $file): ?>
                    <div class="log-container mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4><?php echo htmlspecialchars(str_replace('_', ' ', pathinfo($file, PATHINFO_FILENAME))); ?></h4>
                            <button class="btn btn-sm btn-outline-secondary refresh-btn" data-file="<?php echo htmlspecialchars($file); ?>">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <pre id="<?php echo htmlspecialchars($file); ?>" class="log-content"><?php echo getLogContent($file); ?></pre>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.log-content {
    background-color: #1e1e1e;
    color: #d4d4d4;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
    max-height: 400px;
    overflow-y: scroll;
    font-family: 'Consolas', monospace;
    font-size: 13px;
    line-height: 1.4;
    margin: 0;
    scroll-behavior: smooth;
}

.log-container {
    margin-bottom: 2rem;
}

.refresh-btn {
    transition: all 0.2s ease;
}

.refresh-btn:hover {
    transform: rotate(180deg);
}

.refresh-btn i {
    margin-right: 5px;
}
</style>

<script>
(function() {
    function forceScrollToBottom(element) {
        if (!element) return;
        
        // Try multiple methods to ensure scroll
        try {
            // Method 1: Direct scrollTop
            element.scrollTop = 999999;
            
            // Method 2: ScrollIntoView
            const lastLine = element.lastElementChild || element;
            lastLine.scrollIntoView({ behavior: 'auto', block: 'end' });
            
            // Method 3: Programmatic scroll with animation frame
            requestAnimationFrame(() => {
                element.scrollTop = element.scrollHeight;
                // Double-check scroll after a brief moment
                setTimeout(() => {
                    element.scrollTop = element.scrollHeight;
                }, 50);
            });
        } catch (e) {
            console.error('Scroll error:', e);
        }
    }

    function refreshLog(fileElement) {
        const file = fileElement.id;
        const refreshBtn = document.querySelector(`.refresh-btn[data-file="${file}"]`);
        
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.querySelector('i').classList.add('fa-spin');
        }

        $.ajax({
            url: window.location.pathname + '?action=refresh',
            data: { file: file },
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data && data.status === 'success' && data.content !== undefined) {
                    // Update content and force scroll
                    fileElement.textContent = data.content;
                    forceScrollToBottom(fileElement);
                }
            },
            complete: function() {
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                    refreshBtn.querySelector('i').classList.remove('fa-spin');
                }
            }
        });
    }

    // Initial setup
    document.querySelectorAll('.log-content').forEach(function(elem) {
        // Set initial scroll
        forceScrollToBottom(elem);
        
        // Refresh content
        refreshLog(elem);
    });

    // Setup refresh button clicks
    document.querySelectorAll('.refresh-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const fileId = this.dataset.file;
            const logElement = document.getElementById(fileId);
            if (logElement) {
                refreshLog(logElement);
            }
        });
    });

    // Auto refresh every 5 seconds
    setInterval(function() {
        document.querySelectorAll('.log-content').forEach(function(elem) {
            refreshLog(elem);
        });
    }, 5000);
})();
</script>
