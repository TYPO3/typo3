<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// Register language update command controller
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'TYPO3\\CMS\\Lang\\Command\\LanguageCommandController';
