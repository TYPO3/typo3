<?php

// Register evaluateFieldValue() and deevaluateFieldValue() for input_21 field
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeInput21Eval'] = '';

// Register evaluateFieldValue() and deevaluateFieldValue() for input_21 field
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeText9Eval'] = '';

// Register command controller for console command
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \TYPO3\CMS\Styleguide\Command\StyleguideCommandController::class;
