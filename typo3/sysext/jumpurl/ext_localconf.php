<?php
defined('TYPO3_MODE') or die();

// Register hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlProcessors']['jumpurl']['processor'] = \FoT3\Jumpurl\JumpUrlProcessor::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlHandlers']['jumpurl'] = [
    'handler' => \FoT3\Jumpurl\JumpUrlHandler::class,
    'before' => [
        'frontendExternalUrl'
    ],
];
