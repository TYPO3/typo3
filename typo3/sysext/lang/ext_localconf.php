<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// Register language update command controller
// @deprecated since TYPO3 CMS 6.2 will be removed in 2 versions
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'TYPO3\\CMS\\Lang\\Command\\UpdateCommandController';

// Register language update command controller
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'TYPO3\\CMS\\Lang\\Command\\LanguageCommandController';
