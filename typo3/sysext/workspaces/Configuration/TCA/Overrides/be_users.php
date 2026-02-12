<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

$GLOBALS['TCA']['be_users']['columns']['workspace_perms']['config']['default'] = 0;

ExtensionManagementUtility::addUserSetting(
    'showWorkspaceLiveIndicator',
    [
        'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:userSetting.showWorkspaceLiveIndicator',
        'persistentUpdate' => true,
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 1,
        ],
    ],
    'after:displayRecentlyUsed'
);
