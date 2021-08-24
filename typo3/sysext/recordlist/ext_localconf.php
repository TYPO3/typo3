<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Recordlist\Browser\DatabaseBrowser;
use TYPO3\CMS\Recordlist\Browser\FileBrowser;
use TYPO3\CMS\Recordlist\Browser\FolderBrowser;

defined('TYPO3') or die();

// Register element browsers
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['db'] = DatabaseBrowser::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['file'] = FileBrowser::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['file_reference'] = FileBrowser::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ElementBrowsers']['folder'] = FolderBrowser::class;

// Register default link handlers
ExtensionManagementUtility::addPageTSConfig('
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
