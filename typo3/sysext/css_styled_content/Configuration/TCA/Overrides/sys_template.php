<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'css_styled_content',
    'Configuration/TypoScript/',
    'TypoScript Content Elements'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'css_styled_content',
    'Configuration/TypoScript/Styling/',
    'TypoScript Content Elements CSS (optional)'
);
