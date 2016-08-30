<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE' && !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
    $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = [
        'name' => \TYPO3\CMS\Version\ClickMenu\VersionClickMenu::class,
    ];
}
