<?php

defined('TYPO3') or die();

call_user_func(static function () {

    // Adds the redirect field to the fe_groups table
    $additionalColumns = [
        'felogin_redirectPid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:felogin/Resources/Private/Language/Database.xlf:felogin_redirectPid',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups', $additionalColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_groups', 'felogin_redirectPid', '', 'after:TSconfig');
});
