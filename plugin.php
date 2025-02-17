<?php
// **
// USED TO DEFINE PLUGIN INFORMATION & CLASS
// **

// PLUGIN INFORMATION - This should match what is in plugin.json
$GLOBALS['plugins']['Log Viewer'] = [ // Plugin Name
	'name' => 'Log Viewer', // Plugin Name
	'author' => 'tinytechlabuk', // Who wrote the plugin
	'category' => 'Log Viewer', // One to Two Word Description
	'link' => 'https://github.com/PHP-EF/plugin-log-viewer', // Link to plugin info
	'version' => '1.0.3', // SemVer of plugin
	'image' => 'logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'api' => '/api/plugin/logviewer/settings', // api route for settings page, or null if no settings page
];

class logviewer extends phpef {
	public function __construct() {
		parent::__construct();
	}

    public function getLogFilesFromDirectory($fileExtensions = null) {
	$logFiles = [];
	$this->config->get('Plugins','Log Viewer')['File Extensions'] ?: ['log','txt'];
		$logPaths = $this->getLogPaths();
		foreach ($logPaths as $logDir) {
			if (file_exists($logDir)) {
				$directoryIterator = new RecursiveDirectoryIterator($logDir, FilesystemIterator::SKIP_DOTS);
				$iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
				foreach ($iteratorIterator as $info) {
					if ($fileExtensions) {
						if (in_array($info->getExtension(), $fileExtensions)) {
							$logFiles[] = array('name' => $info->getFilename(), 'value' => $info->getPathname());
						}
					} else {
						$logFiles[] = array('name' => $info->getFilename(), 'value' => $info->getPathname());
					}
				}
			}
		}
			return $logFiles;
	}

    public function getFileExtensions() {
        $extensions = [];
        $logFiles = $this->getLogFilesFromDirectory();
        
        if ($logFiles) {
            foreach ($logFiles as $file) {
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (!in_array($extension, $extensions) && !empty($extension)) {
                    $extensions[] = $extension;
                }
            }
        }
        
        return array_map(function($ext) {
            return [
                "name" => "." . $ext,
                "value" => $ext
            ];
        }, $extensions);
    }

	public function getLogFiles() {
        return $this->config->get('Plugins', 'Log Viewer')['Log Files'] ?? array();
    }

	public function getLogFilesSplit() {
        $LogFiles = $this->config->get('Plugins', 'Log Viewer')['Log Files'] ?? array();
		$LogFilesSplit = [];
		foreach ($LogFiles as $LogFile) {
			$LogFileSplit = explode(',',$LogFile);
			$LogFilesSplit[] = array(
				"base" => $LogFileSplit[0],
				"name" => $LogFileSplit[1]
			);
		}
		return $LogFilesSplit;
    }

	public function getLogPaths() {
        return $this->config->get('Plugins', 'Log Viewer')['logPaths'] ?? array();
    }

	private function findLogFullPathByName($array, $name) {
		foreach ($array as $item) {
			if ($item['name'] === $name) {
				$base = rtrim($item['base'], DIRECTORY_SEPARATOR); // Remove trailing slash if it exists
				return $base . DIRECTORY_SEPARATOR . $item['name'];
			}
		}
		return null; // Return null if the name is not found
	}

	public function getLogContent($filename) {
		$logFiles = $this->getLogFilesSplit();
		$logPath = $this->findLogFullPathByName($logFiles,$filename);
		if (file_exists($logPath)){
			// echo $logPath; //For Debug
			$this->api->setAPIResponseData(htmlspecialchars(file_get_contents($logPath)));
			return;
		}
		$this->api->setAPIResponse('Error','Log file not found in any of the configured paths');
	}

	public function _pluginGetSettings() {
		$LogPaths = $this->getLogPaths();

		$LogFiles = $this->getLogFilesFromDirectory($this->config->get('Plugins','Log Viewer')['File Extensions'] ?? null) ?? null;
		$appendNone = [ "name" => "None", "value" => ""];
		$logFilesKeyValuePairs = [];
		$logFilesKeyValuePairs[] = $appendNone;
		if ($LogFiles) {
			$logFilesKeyValuePairs = array_merge($logFilesKeyValuePairs,array_map(function($item) {
				$basePath = explode($item['name'],$item['value'])[0];
				return [
					"name" => $item['name'],
					"value" => array($basePath,$item['name'])
				];
			}, $LogFiles));
		}

		$FileExtensions = [];
		$FileExtensions[] = $appendNone;
		$FileExtensions = array_merge($FileExtensions,$this->getFileExtensions());

		return array(
			'About' => array (
				$this->settingsOption('notice', '', ['title' => 'Information', 'body' => '
				<p>This is an logviewer plugin.</p>
				<br/>']),
			),
			'Plugin Settings' => array(
				$this->settingsOption('auth', 'ACL-LOGVIEWER', ['label' => 'LogViewer Plugin Read ACL'])
			),
			'Directory Settings' => array(
				$this->settingsOption('input-multiple', 'logPaths', ['label' => 'LogViewer Plugin Directory Paths', 'placeholder' => '/var/www/html/inc/logs/', 'values' => $LogPaths, 'override' => '12', 'text' => 'Add'])
			),
			'Log Files Extension Settings' => array(
				$this->settingsOption('select-multiple', 'File Extensions', ['label' => 'Filter Logs By File Extension', 'options' => $FileExtensions])
			),
			'Log Files Settings' => array(
				$this->settingsOption('select-multiple', 'Log Files', ['label' => 'Log Files', 'options' => $logFilesKeyValuePairs])
			),
		);
	}
}
