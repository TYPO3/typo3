<?php
/**
 * Definitions of routes
 */
return [
	// Register wizard
	'wizard_openid' => [
		'path' => '/wizard/openid',
		'controller' => \TYPO3\CMS\Openid\Wizard::class
	]
];
