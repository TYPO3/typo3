<?php

defined('TYPO3') or die();

/**
 * Add labels for context sensitive help (CSH)
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_BlogExampleTxBlogexampleM1', 'EXT:blog_example/Resources/Private/Language/locallang_csh.xlf');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('blog_example', 'Blogs', 'Blog listing');
