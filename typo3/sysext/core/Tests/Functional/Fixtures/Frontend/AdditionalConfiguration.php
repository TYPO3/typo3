<?php

defined('TYPO3_MODE') or die();

// You may add PHP code here, which is executed on every request after the configuration is loaded.
// The code here should only manipulate TYPO3_CONF_VARS for example to set the database configuration
// dependent to the requested environment.

$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = false;

// Register hooks for frontend test
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Core/TypoScript/TemplateService']['runThroughTemplatesPostProcessing']['FunctionalTest'] =
    \TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Hook\TypoScriptInstructionModifier::class . '->apply';
