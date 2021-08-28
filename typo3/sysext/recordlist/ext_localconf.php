<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Register element browsers
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['db'] = \TYPO3\CMS\Recordlist\Browser\DatabaseBrowser::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['file'] = \TYPO3\CMS\Recordlist\Browser\FileBrowser::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['file_reference'] = \TYPO3\CMS\Recordlist\Browser\FileBrowser::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['folder'] = \TYPO3\CMS\Recordlist\Browser\FolderBrowser::class;

// Register default link handlers
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
TCEMAIN.linkHandler {
  page {
    handler = TYPO3\\CMS\\Recordlist\\LinkHandler\\PageLinkHandler
    label = LLL:EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf:page
  }
  file {
    handler = TYPO3\\CMS\\Recordlist\\LinkHandler\\FileLinkHandler
    label = LLL:EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf:file
    displayAfter = page
    scanAfter = page
  }
  folder {
    handler = TYPO3\\CMS\\Recordlist\\LinkHandler\\FolderLinkHandler
    label = LLL:EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf:folder
    displayAfter = page,file
    scanAfter = page,file
  }
  url {
    handler = TYPO3\\CMS\\Recordlist\\LinkHandler\\UrlLinkHandler
    label = LLL:EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf:extUrl
    displayAfter = page,file,folder
    scanAfter = telephone
  }
  mail {
    handler = TYPO3\\CMS\\Recordlist\\LinkHandler\\MailLinkHandler
    label = LLL:EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf:email
    displayAfter = page,file,folder,url
    scanBefore = url
  }
  telephone {
    handler = TYPO3\\CMS\\Recordlist\\LinkHandler\\TelephoneLinkHandler
    label = LLL:EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf:telephone
    displayAfter = page,file,folder,url,mail
    scanBefore = url
  }
}
');
