<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'TYPO3.CMS.Recycler',
        'web',
        'Recycler',
        '',
        [
            'RecyclerModule' => 'index',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:recycler/Resources/Public/Icons/module-recycler.svg',
            'labels' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_mod.xlf',
        ]
    );
}
