<?php

defined('TYPO3') or die();

if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('seo')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'metatags', '--linebreak--, description', 'after:keywords');
}
