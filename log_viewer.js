// Define the base path for the log folder
const logFolderPath = "/path/to/logs/"; // Update this path as needed

const logFiles = ["packer.txt", "Packer_Powershell_log.txt", "git_pull.txt"];

function refreshLogs() {
    logFiles.forEach(file => {
        const filePath = logFolderPath + file; // Combine the folder path and file name
        fetch(filePath)
            .then(response => response.text())
            .then(data => {
                const logElement = document.getElementById(file);
                logElement.textContent = data;
                logElement.scrollTop = logElement.scrollHeight; // Auto-scroll to bottom
            })
            .catch(error => console.error("Error loading log file:", error));
    });
}

// Auto-refresh every 1 second
setInterval(refreshLogs, 1000);

// Initial load
window.onload = refreshLogs;
