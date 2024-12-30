<?php
// **
// Get LogViewer Plugin Settings
// **
$app->get('/plugin/logviewer/settings', function ($request, $response, $args) {
	$logviewerPlugin = new logviewerPlugin();
	 if ($logviewerPlugin->auth->checkAccess('ACL-Plugin-LogViewer')) {
		$logviewerPlugin->api->setAPIResponseData($logviewerPlugin->_pluginGetSettings());
	// }
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});