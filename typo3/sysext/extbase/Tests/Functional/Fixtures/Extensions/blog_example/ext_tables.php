<?php
defined('TYPO3_MODE') or die();

/**
 * Add labels for context sensitive help (CSH)
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_BlogExampleTxBlogexampleM1', 'EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_csh.xml');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'BlogExample setup');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/DefaultStyles', 'BlogExample CSS Styles (optional)');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin($_EXTKEY, 'Blogs', 'Blog listing');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
    'tx_blogexample_domain_model_blog,tx_blogexample_domain_model_post,tx_blogexample_domain_model_comment,tx_blogexample_domain_model_person,tx_blogexample_domain_model_tag'
);

// Categorize Blog,Post records
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable($_EXTKEY, 'tx_blogexample_domain_model_blog');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable($_EXTKEY, 'tx_blogexample_domain_model_post');
