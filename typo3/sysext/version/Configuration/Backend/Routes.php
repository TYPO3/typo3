<?php

/**
 * Definitions for routes provided by EXT:version
 */
return [
	// Register version_click_module entry point
	'web_txversionM1' => [
		'path' => '/record/versions/',
		'controller' => \TYPO3\CMS\Version\Controller\VersionModuleController::class
	]
];
