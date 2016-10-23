<?php
defined('TYPO3_MODE') or die();

// Add static template for Click-enlarge rendering
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('rtehtmlarea', 'static/clickenlarge/', 'Clickenlarge Rendering');
