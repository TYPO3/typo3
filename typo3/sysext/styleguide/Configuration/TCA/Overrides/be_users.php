<?php

defined('TYPO3') or die();

call_user_func(function () {
    // Add a field to be_users table to identify a user as styleguide demo user
    // Field is handled by DataHandler and is not needed to be shown in BE, so it is of type "passthrough"
    $additionalColumns = [
        'tx_styleguide_isdemorecord' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $additionalColumns);
});
