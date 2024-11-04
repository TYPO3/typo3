<?php

defined('TYPO3') or die();

call_user_func(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'Flexform with Image Reference',
            'testfluidtemplate_flexformdataprocessor',
            'EXT:test_fluid_template/Resources/Public/Icons/Extension.svg',
        ],
    );

    $GLOBALS['TCA']['tt_content']['types']['testfluidtemplate_flexformdataprocessor'] = [
        'showitem' => '
            --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xml:palette.general;general,
                pi_flexform',
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        '*',
        'FILE:EXT:test_fluid_template/Configuration/FlexForms/Simple.xml',
        'testfluidtemplate_flexformdataprocessor'
    );
});
