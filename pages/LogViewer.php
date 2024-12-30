<?php
// Define the log files to monitor
$logFiles = ["php.error.log"]; //,"packer.txt", "Packer_Powershell_log.txt", "git_pull.txt",

// Function to safely read log file content
function getLogContent($filename) {
    $logPath = "/var/www/html/inc/logs/" . basename($filename);
    if (file_exists($logPath)) {
        return htmlspecialchars(file_get_contents($logPath));
    }
    return "Log file not found at: " . $logPath;
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
    function scrollToBottom(element) {
        if (element && element.scrollHeight) {
            element.scrollTop = element.scrollHeight;
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
                    fileElement.textContent = data.content;
                    scrollToBottom(fileElement);
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

    // Initial load and setup
    document.querySelectorAll('.log-content').forEach(function(elem) {
        refreshLog(elem);
        
        // Keep scroll at bottom if already at bottom
        elem.addEventListener('scroll', function() {
            const isAtBottom = elem.scrollHeight - elem.scrollTop === elem.clientHeight;
            if (isAtBottom) {
                elem.dataset.keepScrolled = 'true';
            } else {
                elem.dataset.keepScrolled = 'false';
            }
        });
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
            if (elem.dataset.keepScrolled === 'true') {
                refreshLog(elem);
            }
        });
    }, 5000);
})();
</script>
