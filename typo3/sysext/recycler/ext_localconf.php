<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$TYPO3_CONF_VARS['BE']['AJAX']['tx_recycler::controller'] = \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'classes/controller/class.tx_recycler_controller_ajax.php:TYPO3\\CMS\\Recycler\\Controller\\RecyclerAjaxController->init';
?>