<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

class LogViewer {
    private static $logFiles = ["php.error.log", "packer.txt", "Packer_Powershell_log.txt", "git_pull.txt"];
    private static $logPaths = [
        "/var/www/html/inc/logs/",
        "/mnt/logs/"
    ];

    public static function getLogFiles() {
        return self::$logFiles;
    }

    public static function getLogContent($filename) {
        // Security check - prevent directory traversal
        $filename = basename($filename);
        
        if (empty(self::$logPaths)) {
            error_log("Log paths array is empty or not defined");
            return "Configuration error: Log paths not properly defined";
        }

        foreach (self::$logPaths as $basePath) {
            $logPath = $basePath . $filename;
            
            try {
                if (!is_readable($logPath)) {
                    error_log("File not readable: " . $logPath . " - Current user: " . get_current_user());
                    continue;
                }
                
                if (file_exists($logPath)) {
                    $content = @file_get_contents($logPath);
                    if ($content === false) {
                        $error = error_get_last();
                        error_log("Failed to read file: " . $logPath . " - Error: " . ($error ? $error['message'] : 'Unknown error'));
                        continue;
                    }
                    return htmlspecialchars($content);
                }
            } catch (Exception $e) {
                error_log("Exception reading file: " . $logPath . " - " . $e->getMessage());
                continue;
            }
        }
        
        // Debug information
        error_log("File access attempt details:");
        error_log("Requested file: " . $filename);
        error_log("Current user: " . get_current_user());
        error_log("Current working directory: " . getcwd());
        
        foreach (self::$logPaths as $basePath) {
            if (is_dir($basePath)) {
                $files = @scandir($basePath);
                error_log("Contents of " . $basePath . ": " . ($files ? implode(", ", $files) : "Could not read directory"));
            } else {
                error_log("Directory not accessible: " . $basePath);
            }
        }
        
        return "Log file not found or not readable. Please check PHP error log for details.";
    }
}

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
    try {
        header('Content-Type: application/json');
        $file = isset($_GET['file']) ? $_GET['file'] : '';
        
        if (!in_array($file, LogViewer::getLogFiles())) {
            throw new Exception('Invalid file specified');
        }
        
        echo json_encode([
            'status' => 'success',
            'content' => LogViewer::getLogContent($file)
        ]);
    } catch (Exception $e) {
        error_log("Error in AJAX request: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
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
                    <?php foreach (LogViewer::getLogFiles() as $file): ?>
                    <div class="log-container mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4><?php echo htmlspecialchars(str_replace('_', ' ', pathinfo($file, PATHINFO_FILENAME))); ?></h4>
                            <button class="btn btn-sm btn-outline-secondary refresh-btn" data-file="<?php echo htmlspecialchars($file); ?>">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <pre id="<?php echo htmlspecialchars($file); ?>" class="log-content"><?php echo LogViewer::getLogContent($file); ?></pre>
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
