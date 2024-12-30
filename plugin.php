<?php
// **
// USED TO DEFINE PLUGIN INFORMATION & CLASS
// **

// PLUGIN INFORMATION - This should match what is in plugin.json
$GLOBALS['plugins']['logviewer'] = [ // Plugin Name
	'name' => 'logviewer', // Plugin Name
	'author' => 'jamiedonaldson-tinytechlabuk', // Who wrote the plugin
	'category' => 'logviewer', // One to Two Word Description
	'link' => 'https://github.com/jamiedonaldson-tinytechlabuk/php-ef-log-viewer-plugin', // Link to plugin info
	'version' => '1.0.0', // SemVer of plugin
	'image' => 'logo.png', // 1:1 non transparent image for plugin
	'settings' => false, // does plugin need a settings modal?
	'api' => '/api/plugin/logviewer/settings', // api route for settings page, or null if no settings page
];

class logviewer extends ib {

	private $logFiles = ["php.error.log", "Packer.txt", "Packer_Powershell_log.txt", "Packer_git_pull.txt"];
    private $logPaths = [
        "/var/www/html/inc/logs/",
        "/mnt/logs/"
    ];

	public function __construct() {
		parent::__construct();
	}

    public function getLogFiles() {
        return $this->logFiles;
    }

	public function getLogContent($filename) {
		
		foreach ($this->logPaths as $basePath) {
			$logPath = $basePath . basename($filename);
			if (file_exists($logPath)) {
				$this->api->setAPIResponseData(htmlspecialchars(file_get_contents($logPath)));
			}
		}
		$this->api->setAPIResponse('Error','Log file not found in any of the configured paths');
	}

	public function _pluginGetSettings()
	{
		return array(
			'About' => array (
				$this->settingsOption('notice', '', ['title' => 'Information', 'body' => '
				<p>This is an logviewer plugin.</p>
				<br/>']),
			),
			'Plugin Settings' => array(
				$this->settingsOption('auth', 'ACL-READ', ['label' => 'Plugin Read ACL']),
				$this->settingsOption('auth', 'ACL-WRITE', ['label' => 'Plugin Write ACL']),
				$this->settingsOption('password', 'Password', ['label' => 'Some Password']),
				$this->settingsOption('input', 'Config1', ['label' => 'Some option 1']),
				$this->settingsOption('input', 'Config2', ['label' => 'Some option 2']),
				$this->settingsOption('blank'),
				$this->settingsOption('input', 'Config3', ['label' => 'Some option 3']),
				$this->settingsOption('button', '', ['label' => 'Undo', 'icon' => 'fa fa-undo', 'text' => 'Retrieve', 'attr' => 'onclick="doSomething();"']),
			),
			'Connection Settings' => array(
				$this->settingsOption('url', 'URL'),
				$this->settingsOption('password-alt', 'Token'),
				$this->settingsOption('select', 'Server', ['label' => 'Preferred Server', 'options' => array(array("name" => 'Option 1', "value" => 'opt1'),array("name" => 'Option 2', "value" => 'opt2'))]),
			),
		);
	}
}