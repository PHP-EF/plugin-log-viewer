<?php
// Define the log files to monitor
$logFiles = ["packer.txt", "Packer_Powershell_log.txt", "git_pull.txt", "php.error.log"];

// Function to safely read log file content
function getLogContent($filename) {
    $logPath = __DIR__ . "/../logs/" . basename($filename); // Fixed path to logs directory
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
</head>
<body>
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

    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            function refreshLog(fileElement) {
                const file = $(fileElement).attr('id');
                const refreshBtn = $(`.refresh-btn[data-file="${file}"]`);
                
                refreshBtn.prop('disabled', true);
                refreshBtn.find('i').addClass('fa-spin');

                $.ajax({
                    url: '?action=refresh',
                    data: { file: file },
                    method: 'GET',
                    success: function(response) {
                        if (response.status === 'success') {
                            $(fileElement).text(response.content);
                            $(fileElement).scrollTop($(fileElement)[0].scrollHeight);
                        } else {
                            toast("Error", "", response.message, "danger");
                        }
                    },
                    error: function() {
                        toast("Error", "", "Failed to refresh log content", "danger");
                    },
                    complete: function() {
                        refreshBtn.prop('disabled', false);
                        refreshBtn.find('i').removeClass('fa-spin');
                    }
                });
            }

            // Initial load
            $('.log-content').each(function() {
                refreshLog(this);
            });

            // Setup refresh button clicks
            $('.refresh-btn').click(function() {
                const fileId = $(this).data('file');
                refreshLog($('#' + fileId));
            });

            // Auto refresh every 5 seconds
            setInterval(function() {
                $('.log-content').each(function() {
                    refreshLog(this);
                });
            }, 5000);
        });
    </script>
</body>
</html>
