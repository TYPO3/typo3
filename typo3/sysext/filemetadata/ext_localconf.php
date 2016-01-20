<?php
defined('TYPO3_MODE') or die();

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);

$iconRegistry->registerIcon(
    'filemetadata-status-1',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    ['name' => 'check']
);
$iconRegistry->registerIcon(
    'filemetadata-status-2',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    ['name' => 'clock-o']
);
$iconRegistry->registerIcon(
    'filemetadata-status-3',
    \TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider::class,
    ['name' => 'eye']
);
