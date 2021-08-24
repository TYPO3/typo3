<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

/**
 * Add labels for context sensitive help (CSH)
 */
ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_BlogExampleTxBlogexampleM1', 'EXT:blog_example/Resources/Private/Language/locallang_csh.xlf');

ExtensionUtility::registerPlugin('blog_example', 'Blogs', 'Blog listing');
