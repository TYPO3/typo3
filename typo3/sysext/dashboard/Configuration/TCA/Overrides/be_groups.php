<?php

defined('TYPO3') or die();

call_user_func(static function () {
    $additionalColumns = [
        'availableWidgets' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:availableWidgets',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Dashboard\WidgetRegistry::class . '->widgetItemsProcFunc',
                'size' => 5,
                'autoSizeMax' => 50,
            ],
        ],

    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $additionalColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'be_groups',
        'availableWidgets',
        '',
        'after:groupMods'
    );
});
