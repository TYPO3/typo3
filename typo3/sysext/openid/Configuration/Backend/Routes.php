<?php
/**
 * Definitions of routes
 */
return [
	// Register wizard
	'wizard_openid' => [
		'path' => '/wizard/openid',
		'target' => \TYPO3\CMS\Openid\Wizard::class . '::mainAction'
	]
];
