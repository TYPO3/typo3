<?php

defined('TYPO3_MODE') or die();

// Register icons not being part of TYPO3.Icons repository
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'status-reference-hard',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:impexp/Resources/Public/Icons/status-reference-hard.png']
);
$iconRegistry->registerIcon(
    'status-reference-soft',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:impexp/Resources/Public/Icons/status-reference-soft.png']
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \TYPO3\CMS\Impexp\Hook\BackendControllerHook::class . '->addJavaScript';
$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418735] = \TYPO3\CMS\Impexp\ContextMenu\ItemProvider::class;
