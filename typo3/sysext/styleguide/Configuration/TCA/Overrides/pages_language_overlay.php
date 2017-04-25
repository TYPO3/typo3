<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    // Add a field to pages_language_overlay table to identify styleguide demo pages overlays.
    // Field is handled by DataHandler and is not needed to be shown in BE, so it is of type "passthrough"
    $additionalColumns = [
        'tx_styleguide_containsdemo' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages_language_overlay', $additionalColumns);
});
