<?php
require_once 'Bootstrap'.DIRECTORY_SEPARATOR.'Api.php';
Typo3_Bootstrap_Api::baseSetup();
Typo3_Bootstrap_Api::includeRequiredClasses();
Typo3_Bootstrap_Api::initializeCachingFramework();

try {
	$dispatcher = t3lib_div::makeInstance('t3lib_webservice_dispatcher');
	$dispatcher->dispatch(t3lib_div::getIndpEnv('REQUEST_URI'));
} catch (t3lib_error_webservice_EarlyExitException $e) {
	exit;
}
