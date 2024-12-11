<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline use combination',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
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
                'foreign_table' => 'tx_styleguide_inline_usecombination_mm',
                'foreign_field' => 'select_parent',
                'foreign_selector' => 'select_child',
                'foreign_unique' => 'select_child',
                'maxitems' => 9999,
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

        'inline_2' => [
            'label' => 'inline_2',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_usecombination_mm',
                'foreign_field' => 'select_parent',
                'foreign_selector' => 'select_child',
                'foreign_unique' => 'select_child',
                'maxitems' => 9999,
                // @todo: The difference inline_2 to inline_1 is this 'autoSizeMax' setting which leads to
                //        different UI rendering. The example re-uses the same "mm" table, which is not a
                //        realistic nor fully supported scenario since it leads to ambiguity when looking
                //        at the relation from the MM side "up" to the inline parent. This misuse is accepted
                //        for this scenario for now.
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
            'showitem' => 'inline_1, inline_2',
        ],
    ],

];
