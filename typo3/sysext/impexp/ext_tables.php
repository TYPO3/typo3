<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']['impexp'][\TYPO3\CMS\Impexp\Task\ImportExportTask::class] = [
        'title' => 'LLL:EXT:impexp/Resources/Private/Language/locallang_csh.xlf:.alttitle',
        'description' => 'LLL:EXT:impexp/Resources/Private/Language/locallang_csh.xlf:.description',
        'icon' => 'EXT:impexp/Resources/Public/Images/export.gif'
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('xMOD_tx_impexp', 'EXT:impexp/Resources/Private/Language/locallang_csh.xlf');

    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418735] = \TYPO3\CMS\Impexp\ContextMenu\ItemProvider::class;
}
