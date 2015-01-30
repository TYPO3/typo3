<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Form\Utility\FormUtility::getInstance()->initializeFormObjects()->initializePageTsConfig();

// Add a for previewing tt_content elements of CType="mailform"
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['mailform'] = \TYPO3\CMS\Form\Hooks\PageLayoutView\MailformPreviewRenderer::class;
