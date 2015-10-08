<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('fluid_styled_content', 'Configuration/TypoScript/Static/', 'Content Elements');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('fluid_styled_content', 'Configuration/TypoScript/Styling/', 'Content Elements CSS (optional)');
