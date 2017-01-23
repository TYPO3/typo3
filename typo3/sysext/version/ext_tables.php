<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE' && !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418676] = \TYPO3\CMS\Version\ContextMenu\ItemProvider::class;
}
