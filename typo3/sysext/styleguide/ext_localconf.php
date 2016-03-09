<?php

// Register evaluateFieldValue() and deevaluateFieldValue() for input_21 field
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeInput21Eval'] = '';

// Register evaluateFieldValue() and deevaluateFieldValue() for input_21 field
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeText9Eval'] = '';

// Register command controller for console command
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \TYPO3\CMS\Styleguide\Command\StyleguideCommandController::class;

/** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'tcarecords-tx_styleguide_forms-default',
    TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [ 'source' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg' ]
);
