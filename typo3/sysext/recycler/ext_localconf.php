<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('RecyclerAjaxController::init', \TYPO3\CMS\Recycler\Controller\RecyclerAjaxController::class . '->init');
}
