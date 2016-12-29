<?php
defined('TYPO3_MODE') or die();

// Mark the delivered TypoScript templates as "content rendering template"
$GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'][] = 'cssstyledcontent/Configuration/TypoScript/';

// TYPO3 CMS 8 is the last version that supports CSS styled content.
// This extension will only receive security updates in the future,
// and will finally be removed from the TYPO3 Core in CMS 9.
//
// Fluid styled content and CSS styled content are now sharing the same featureset
// so you can now benefit from more flexible templates and adjustments without
// leaving any nessesary features behind.
\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
    'The core extension CSS styled content has been deprecated since TYPO3 CMS 8 and will be removed in TYPO3 CMS 9.'
);
