<?php
defined('TYPO3') or die();

call_user_func(function()
{
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'styleguide',
        'Configuration/TypoScript',
        'Styleguide Demo Frontend'
    );
});
