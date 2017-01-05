<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {

    // Adds the redirect field and the forgotHash field to the fe_users-table
    $additionalColumns = [
        'felogin_redirectPid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:felogin/Resources/Private/Language/Database.xlf:felogin_redirectPid',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ]
        ],
        'felogin_forgotHash' => [
            'exclude' => true,
            'label' => 'LLL:EXT:felogin/Resources/Private/Language/Database.xlf:felogin_forgotHash',
            'config' => [
                'type' => 'passthrough'
            ]
        ]
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $additionalColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'felogin_redirectPid', '', 'after:TSconfig');
});
