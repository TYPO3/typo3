<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('blog_example', 'Configuration/TypoScript', 'BlogExample setup');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('blog_example', 'Configuration/TypoScript/DefaultStyles', 'BlogExample CSS Styles (optional)');
