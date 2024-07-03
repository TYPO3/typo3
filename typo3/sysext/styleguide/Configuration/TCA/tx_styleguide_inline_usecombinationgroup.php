<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline use combination group',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'inline_1' => [
            'label' => 'inline_1',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_usecombinationgroup_mm',
                'foreign_field' => 'group_parent',
                'foreign_selector' => 'group_child',
                'foreign_unique' => 'group_child',
                'maxitems' => 9999,
                'autoSizeMax' => 10,
                'appearance' => [
                    'newRecordLinkAddTitle' => 1,
                    'useCombination' => true,
                    'collapseAll' => false,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 'inline_1',
        ],
    ],

];
