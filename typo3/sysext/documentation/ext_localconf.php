<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

// Open extension manual from within Extension Manager
$signalSlotDispatcher->connect(
	'TYPO3\\CMS\\Extensionmanager\\ViewHelpers\\ProcessAvailableActionsViewHelper',
	'processActions',
	'TYPO3\\CMS\Documentation\\Slots\\ExtensionManager',
	'processActions'
);