<?php
// **
// Get LogViewer Plugin Settings
// **
$app->get('/plugin/logviewer/settings', function ($request, $response, $args) {
	$logviewer = new logviewer();
	 if ($logviewer->auth->checkAccess($logviewer->config->get("Plugins", "logviewer")['ACL-LOGVIEWER'] ?: "ACL-LOGVIEWER")) {
		$logviewer->api->setAPIResponseData($logviewer->_pluginGetSettings());
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});

$app->get('/plugin/logviewer/tail', function ($request, $response, $args) {
	$logviewer = new logviewer();
	 if ($logviewer->auth->checkAccess($logviewer->config->get("Plugins", "logviewer")['ACL-LOGVIEWER'] ?: "ACL-LOGVIEWER")) {
		$data = $request->getQueryParams();
		if (isset($data['file'])) {
			$logviewer->getLogContent($file);
		} else {
			$logviewer->api->setAPIResponse('Error','File not specified');
		}
	}
	$response->getBody()->write(jsonE($GLOBALS['api']));
	return $response
		->withHeader('Content-Type', 'application/json;charset=UTF-8')
		->withStatus($GLOBALS['responseCode']);
});