<?php

defined('TYPO3') or die();

call_user_func(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'Flexform with Image Reference',
            'testfluidtemplate_flexformdataprocessor',
            'EXT:test_fluid_template/Resources/Public/Icons/Extension.svg',
        ],
        'FILE:EXT:test_fluid_template/Configuration/FlexForms/Simple.xml'
    );
});
