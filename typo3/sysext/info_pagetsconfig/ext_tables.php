<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_info',
		'TYPO3\CMS\InfoPagetsconfig\Controller\InfoPageTyposcriptConfigController',
		NULL,
		'LLL:EXT:info_pagetsconfig/locallang.xlf:mod_pagetsconfig'
	);
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_info', 'EXT:info_pagetsconfig/locallang_csh_webinfo.xlf');
