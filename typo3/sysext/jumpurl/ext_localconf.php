<?php
defined('TYPO3_MODE') or die();

// Register hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlProcessors']['jumpurl']['processor'] = \TYPO3\CMS\Jumpurl\JumpUrlProcessor::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlHandlers']['jumpurl'] = [
    'handler' => \TYPO3\CMS\Jumpurl\JumpUrlHandler::class,
    'before' => [
        'frontendExternalUrl'
    ],
];
