<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	// Add module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('tools', 'txschedulerM1', '', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/');
	// Add context sensitive help (csh) to the backend module
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_tools_txschedulerM1', 'EXT:' . $_EXTKEY . '/mod1/locallang_csh_scheduler.xml');

	// Register specific icon for run task button
	\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons(
		array(
			'run-task' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Images/Icons/RunTask.png'
		),
		$_EXTKEY
	);

}
?>