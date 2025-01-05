<?php
$logviewer = new logviewer();
$pluginConfig = $logviewer->config->get('Plugins','Log Viewer');
if ($logviewer->auth->checkAccess($pluginConfig['ACL-LOGVIEWER'] ?? null) == false) {
    $ib->api->setAPIResponse('Error','Unauthorized',401);
    return false;
};
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Log Viewer</h3>
                </div>
                <div class="card-body">
                    <?php
                    foreach ($logviewer->getLogFilesSplit() as $file): ?>
                    <div class="log-container mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4><?php echo htmlspecialchars(str_replace('_', ' ', pathinfo($file['name'], PATHINFO_FILENAME))); ?></h4>
                            <button class="btn btn-sm btn-outline-secondary refresh-btn" data-file="<?php echo htmlspecialchars($file['name']); ?>">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <pre id="<?php echo htmlspecialchars($file['name']); ?>" class="log-content"><?php echo $logviewer->getLogContent($file['name']); ?></pre>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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

        queryAPI("GET","/api/plugin/logviewer/tail?file="+file).done(function(data) {
            if (data["result"] == "Success") {
                // Update content and force scroll
                fileElement.textContent = data.data;
                forceScrollToBottom(fileElement);
            } else if (data["result"] == "Error") {
                toast(data["result"],"",data["message"]+": "+file,"danger");
            } else {
                toast("API Error","","Failed to load log file: "+file,"danger","30000");
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            toast(textStatus,"","Failed to load log file: "+file+".<br>"+jqXHR.status+": "+errorThrown,"danger");
        }).always(function() {
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.querySelector('i').classList.remove('fa-spin');
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
