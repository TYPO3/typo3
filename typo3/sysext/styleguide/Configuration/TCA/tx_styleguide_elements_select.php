<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - select',
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
        'select_single_1' => [
            'label' => 'select_single_1 two items, long text description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'foo and this here is very long text that maybe does not really fit into the form in one line.'
                            . ' Ok let us add even more text to see how this looks like if wrapped. Is this enough now? No?'
                            . ' Then let us add some even more useless text here!',
                        'value' => 1,
                    ],
                    ['label' => 'bar', 'value' => 'bar'],
                ],
            ],
        ],
        'select_single_2' => [
            'label' => 'select_single_2 itemsProcFunc',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'foo', 'value' => 1],
                    ['label' => 'bar', 'value' => 'bar'],
                ],
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeSelect2ItemsProcFunc->itemsProcFunc',
            ],
        ],
        'select_single_3' => [
            'label' => 'select_single_3 static values, dividers, foreign_table_where',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Static values', 'value' => '--div--'],
                    ['label' => 'static -2', 'value' => -2],
                    ['label' => 'static -1', 'value' => -1],
                    ['label' => 'DB values', 'value' => '--div--'],
                ],
                'foreign_table' => 'tx_styleguide_staticdata',
                'foreign_table_where' => 'AND {#tx_styleguide_staticdata}.{#value_1} LIKE \'%foo%\' ORDER BY uid',
                'foreign_table_prefix' => 'A prefix: ',
            ],
        ],
        'select_single_4' => [
            'label' => 'select_single_4 items with icons',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'foo 1', 'value' => 'foo1', 'icon' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg'],
                    ['label' => 'foo 2', 'value' => 'foo2', 'icon' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg'],
                ],
            ],
        ],
        'select_single_5' => [
            'label' => 'select_single_5 selectIcons, items with icons',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'foo 1', 'value' => 'foo1', 'icon' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg'],
                    ['label' => 'foo 2', 'value' => 'foo2', 'icon' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg'],
                ],
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'select_single_7' => [
            'label' => 'select_single_7 fileFolder, dummy first entry, selectIcons',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'fileFolderConfig' => [
                    'folder' => 'EXT:styleguide/Resources/Public/Icons',
                    'allowedExtensions' => 'svg',
                    'depth' => 1,
                ],
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'select_single_8' => [
            'label' => 'select_single_8 drop down with empty div',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'First div with items', 'value' => '--div--'],
                    ['label' => 'item 1', 'value' => 1],
                    ['label' => 'item 2', 'value' => 2],
                    ['label' => 'Second div without items', 'value' => '--div--'],
                    ['label' => 'Third div with items', 'value' => '--div--'],
                    ['label' => 'item 3', 'value' => 3],
                ],
            ],
        ],
        // @todo: selectSingle with size > 1 overlaps with selectSingleBox, except that only one item can be selected
        'select_single_10' => [
            'label' => 'select_single_10 size=6, three options',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'a divider', 'value' => '--div--'],
                    ['label' => 'foo 3', 'value' => 3],
                ],
                'size' => 6,
            ],
        ],
        'select_single_11' => [
            'label' => 'select_single_11 size=2, two options',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                ],
                'size' => 2,
            ],
        ],
        'select_single_12' => [
            'label' => 'select_single_12 foreign_table selicon_field',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_elements_select_single_12_foreign',
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'select_single_13' => [
            'label' => 'select_single_13 l10n_display=defaultAsReadonly',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'foo', 'value' => 'foo'],
                    ['label' => 'bar', 'value' => 'bar'],
                ],
            ],
        ],
        'select_single_14' => [
            'label' => 'select_single_14 two items readOnly description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'readOnly' => true,
                'items' => [
                    ['label' => 'bar', 'value' => 'bar'],
                ],
            ],
        ],
        'select_single_15' => [
            'label' => 'select_single_15 foreign_table',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_staticdata',
                'MM' => 'tx_styleguide_elements_select_single_15_mm',
            ],
        ],
        'select_single_16' => [
            'label' => 'select_single_16',
            'description' => 'itemGroups',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'item 1', 'value' => 1, 'group' => 'group1'],
                    ['label' => 'item 2', 'value' => 2, 'group' => 'group1'],
                    ['label' => 'item 3', 'value' => 3, 'group' => 'group3'],
                    ['label' => 'item 4', 'value' => 3],
                ],
                'itemGroups' => [
                    'group1' => 'Group 1 with items',
                    'group2' => 'Group 2 with no items',
                    'group3' => 'Group 3 with items',
                ],
            ],
        ],
        'select_single_17' => [
            'label' => 'select_single_16',
            'description' => 'itemGroups, size=6',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'item 1', 'value' => 1, 'group' => 'group1'],
                    ['label' => 'item 2', 'value' => 2, 'group' => 'group1'],
                    ['label' => 'item 3', 'value' => 3, 'group' => 'group3'],
                    ['label' => 'item 4', 'value' => 3],
                ],
                'itemGroups' => [
                    'group1' => 'Group 1 with items',
                    'group2' => 'Group 2 with no items',
                    'group3' => 'Group 3 with items',
                ],
                'size' => 6,
            ],
        ],
        'select_single_18' => [
            'label' => 'select_single_18',
            'description' => 'sortItems label asc',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Plum tree', 'value' => 1],
                    ['label' => 'Walnut tree', 'value' => 2],
                    ['label' => 'Apple tree', 'value' => 3],
                    ['label' => 'Cherry tree', 'value' => 4],
                ],
                'sortItems' => [
                    'label' => 'asc',
                ],
                'size' => 4,
            ],
        ],
        'select_single_19' => [
            'label' => 'select_single_19',
            'description' => 'sortItems value desc',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Plum tree', 'value' => 1],
                    ['label' => 'Walnut tree', 'value' => 2],
                    ['label' => 'Apple tree', 'value' => 3],
                    ['label' => 'Cherry tree', 'value' => 4],
                ],
                'sortItems' => [
                    'value' => 'desc',
                ],
                'size' => 4,
            ],
        ],
        'select_single_20' => [
            'label' => 'select_single_20',
            'description' => 'sortItems custom',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Plum tree', 'value' => 1],
                    ['label' => 'Walnut tree', 'value' => 2],
                    ['label' => 'Apple tree', 'value' => 3],
                    ['label' => 'Cherry tree', 'value' => 4],
                ],
                'sortItems' => [
                    'tx_styleguide' => 'TYPO3\CMS\Styleguide\UserFunctions\FormEngine\SelectItemSorter->sortReverseTitles',
                ],
                'size' => 4,
            ],
        ],
        'select_single_21' => [
            'label' => 'select_single_21',
            'description' => 'itemGroups, foreign_table',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'allowNonIdValues' => true,
                'items' => [
                    ['label' => 'static item 1', 'value' => 'static-1', 'group' => 'group1'],
                    ['label' => 'static item 2', 'value' => 'static-2', 'group' => 'group1'],
                    ['label' => 'static item 3', 'value' => 'static-3', 'group' => 'undefined'],
                ],
                'itemGroups' => [
                    'group1' => 'Group 1 with items',
                    'group2' => 'Group 2 with no items',
                    'group3' => 'Group 3 from foreign table',
                    // Group 4 uses locallang label
                    'group4' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:itemGroupLabel',
                ],
                'foreign_table' => 'tx_styleguide_elements_select_single_21_foreign',
                'foreign_table_item_group' => 'item_group',
            ],
        ],
        'select_singlebox_1' => [
            'label' => 'select_singlebox_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'divider', 'value' => '--div--'],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'foo 4', 'value' => 4],
                ],
            ],
        ],
        'select_singlebox_2' => [
            'label' => 'select_singlebox_2 readOnly description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'readOnly' => true,
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'divider', 'value' => '--div--'],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'foo 4', 'value' => 4],
                ],
            ],
        ],
        'select_singlebox_3' => [
            'label' => 'select_singlebox_3',
            'description' => 'itemGroups',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'items' => [
                    ['label' => 'item 1', 'value' => 1, 'group' => 'group1'],
                    ['label' => 'item 2', 'value' => 2, 'group' => 'group1'],
                    ['label' => 'item 3', 'value' => 3, 'group' => 'group3'],
                    ['label' => 'item 4', 'value' => 3],
                ],
                'itemGroups' => [
                    'group1' => 'Group 1 with items',
                    'group2' => 'Group 2 with no items',
                    'group3' => 'Group 3 with items',
                ],
            ],
        ],

        'select_checkbox_1' => [
            'label' => 'select_checkbox_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'foo 4 (empty)', 'value' => ''],
                ],
            ],
        ],
        'select_checkbox_2' => [
            'label' => 'select_checkbox_2, relationship=manyToOne',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'relationship' => 'manyToOne',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'foo 3', 'value' => 3],
                ],
            ],
        ],
        'select_checkbox_3' => [
            'label' => 'select_checkbox_3 icons, description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1, 'description' => ['title' => 'optional title', 'description' => 'optional description']],
                    ['label' => 'foo 2', 'value' => 2, 'icon' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg', 'description' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:translatedHelpTextForSelectCheckBox3'],
                    ['label' => 'foo 3', 'value' => 3, 'icon' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg'],
                    ['label' => 'foo 4', 'value' => 4],
                ],
            ],
        ],
        'select_checkbox_4' => [
            'label' => 'select_checkbox_4 readOnly description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'readOnly' => true,
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                ],
            ],
        ],
        'select_checkbox_5' => [
            'label' => 'select_checkbox_5 dividers, expandAll',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'appearance' => [
                    'expandAll' => true,
                ],
                'items' => [
                    ['label' => 'div 1', 'value' => '--div--'],
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'div 2', 'value' => '--div--'],
                    ['label' => 'foo 4', 'value' => 4],
                    ['label' => 'foo 5', 'value' => 5],
                ],
            ],
        ],
        'select_checkbox_6' => [
            'label' => 'select_checkbox_5 dividers',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['label' => 'div 1', 'value' => '--div--'],
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'div 2', 'value' => '--div--'],
                    ['label' => 'foo 4', 'value' => 4],
                    ['label' => 'foo 5', 'value' => 5],
                ],
            ],
        ],
        'select_checkbox_7' => [
            'label' => 'select_checkbox_7',
            'description' => 'itemGroups',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1, 'group' => 'group1'],
                    ['label' => 'foo 2', 'value' => 2, 'icon' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg', 'group' => 'group1'],
                    ['label' => 'foo 3', 'value' => 3, 'icon' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg'],
                    ['label' => 'foo 4', 'value' => 4],
                    ['label' => 'foo 5', 'value' => 1, 'group' => 'group3'],
                ],
                'itemGroups' => [
                    'group1' => 'Group 1 with items',
                    'group2' => 'Group 2 with no items',
                    'group3' => 'Group 3 with items',
                ],
            ],
        ],

        'select_multiplesidebyside_1' => [
            'label' => 'select_multiplesidebyside_1 autoSizeMax=10, size=3 description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'a divider', 'value' => '--div--'],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'foo 4', 'value' => 4],
                    ['label' => 'foo 5', 'value' => 5],
                    ['label' => 'foo 6', 'value' => 6],
                ],
                'size' => 3,
                'autoSizeMax' => 10,
                'multiple' => true,
            ],
        ],
        'select_multiplesidebyside_2' => [
            'label' => 'select_multiplesidebyside_2 exclusiveKeys=1,2',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['label' => 'two exclusive items', 'value' => '--div--'],
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'casual multiple items', 'value' => '--div--'],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'foo 4', 'value' => 4],
                    ['label' => 'foo 5', 'value' => 5],
                    ['label' => 'foo 6', 'value' => 6],
                ],
                'multiple' => true,
                'exclusiveKeys' => '1,2',
            ],
        ],
        'select_multiplesidebyside_3' => [
            'label' => 'select_multiplesidebyside_3 itemListStyle, selectedListStyle',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'foo 3', 'value' => 3],
                ],
                'itemListStyle' => 'width:250px;background-color:#ffcccc;',
                'selectedListStyle' => 'width:250px;background-color:#ccffcc;',
                'size' => 2,
            ],
        ],
        'select_multiplesidebyside_5' => [
            'label' => 'select_multiplesidebyside_5 multiSelectFilterItems',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'bar', 'value' => 4],
                ],
                'multiSelectFilterItems' => [
                    ['', ''],
                    ['foo', 'foo'],
                    ['bar', 'bar'],
                ],
            ],
        ],
        'select_multiplesidebyside_6' => [
            'label' => 'select_multiplesidebyside_6 fieldControl',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_styleguide_staticdata',
                'size' => 5,
                'autoSizeMax' => 20,
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => false,
                        'options' => [
                            'windowOpenParameters' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                        ],
                    ],
                    'addRecord' => [
                        'disabled' => false,
                    ],
                    'listModule' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'select_multiplesidebyside_7' => [
            'label' => 'select_multiplesidebyside_7 readonly description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'a divider', 'value' => '--div--'],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'foo 4', 'value' => 4],
                    ['label' => 'foo 5', 'value' => 5],
                    ['label' => 'foo 6', 'value' => 6],
                ],
                'readOnly' => true,
                'size' => 3,
                'autoSizeMax' => 10,
                'multiple' => true,
            ],
        ],
        'select_multiplesidebyside_8' => [
            'label' => 'select_multiplesidebyside_8 foreign_table mm',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_styleguide_staticdata',
                'MM' => 'tx_styleguide_elements_select_multiplesidebyside_8_mm',
                'size' => 3,
                'autoSizeMax' => 10,
            ],
        ],
        'select_multiplesidebyside_9' => [
            'label' => 'select_multiplesidebyside_9 relationship=manyToOne',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'relationship' => 'manyToOne',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'bar', 'value' => 4],
                ],
            ],
        ],
        'select_multiplesidebyside_10' => [
            'label' => 'select_multiplesidebyside_1 autoSizeMax=10, size=3 description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['label' => 'item 1', 'value' => 1, 'group' => 'group1'],
                    ['label' => 'item 2', 'value' => 2, 'group' => 'group1'],
                    ['label' => 'item 3', 'value' => 3, 'group' => 'group3'],
                    ['label' => 'item 4', 'value' => 4],
                ],
                'itemGroups' => [
                    'group1' => 'Group 1 with items',
                    'group2' => 'Group 2 with no items',
                    'group3' => 'Group 3 with items',
                ],
                'size' => 3,
                'autoSizeMax' => 10,
                'multiple' => true,
            ],
        ],
        'select_tree_1' => [
            'label' => 'select_tree_1 pages, showHeader=true, expandAll=true, size=20, order by sorting, static items, description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'foreign_table_where' => 'ORDER BY pages.sorting',
                'size' => 20,
                'items' => [
                    ['label' => 'static from tca 4711', 'value' => 4711],
                    ['label' => 'static from tca 4712', 'value' => 4712],
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
                'treeConfig' => [
                    'parentField' => 'pid',
                    'appearance' => [
                        'expandAll' => true,
                        'showHeader' => true,
                    ],
                ],
            ],
        ],
        'select_tree_2' => [
            'label' => 'select_tree_2 pages, showHeader=false, nonSelectableLevels=0,1, maxitems=4, size=10',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'maxitems' => 4,
                'size' => 10,
                'treeConfig' => [
                    'parentField' => 'pid',
                    'appearance' => [
                        'expandAll' => true,
                        'showHeader' => false,
                        'nonSelectableLevels' => '0,1',
                    ],
                ],
            ],
        ],
        'select_tree_3' => [
            'label' => 'select_tree_3 pages, maxLevels=1, minitems=1, maxitems=2',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'size' => 20,
                'minitems' => 1,
                'maxitems' => 2,
                'treeConfig' => [
                    'parentField' => 'pid',
                    'appearance' => [
                        'showHeader' => true,
                        'expandAll' => true,
                        'maxLevels' => 1,
                    ],
                ],
            ],
        ],
        'select_tree_4' => [
            'label' => 'select_tree_4 pages, maxLevels=2, requestUpdate, expandAll=false',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'size' => 20,
                'maxitems' => 4,
                'treeConfig' => [
                    'parentField' => 'pid',
                    'appearance' => [
                        'expandAll' => false,
                        'showHeader' => true,
                        'maxLevels' => 2,
                    ],
                ],
            ],
        ],
        'select_tree_5' => [
            'label' => 'select_tree_5 pages, readOnly, showHeader=true description',
            'description' => 'field description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'size' => 20,
                'readOnly' => true,
                'maxitems' => 4,
                'treeConfig' => [
                    'parentField' => 'pid',
                    'appearance' => [
                        'showHeader' => true,
                        'expandAll' => true,
                    ],
                ],
            ],
        ],
        'select_tree_6' => [
            'label' => 'select_tree_6 categories',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'sys_category',
                'foreign_table_where' => 'AND ({#sys_category}.{#sys_language_uid} = 0 OR {#sys_category}.{#l10n_parent} = 0) ORDER BY sys_category.sorting',
                'size' => 20,
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => [
                        'expandAll' => true,
                        'showHeader' => true,
                    ],
                ],
            ],
        ],

        'category_11' => [
            'label' => 'category_11',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
            ],
        ],
        'category_1n' => [
            'label' => 'category_1n',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToMany',
            ],
        ],
        'category_mm' => [
            'label' => 'category_mm',
            // TcaPreparation sets exclude by default, but styleguide has no individual exclude fields
            'exclude' => false,
            'config' => [
                'type' => 'category',
            ],
        ],

        'select_requestUpdate_1' => [
            'label' => 'select_requestUpdate_1',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Just an item', 'value' => 1],
                    ['label' => 'bar', 'value' => 'bar'],
                    ['label' => 'and yet another one', 'value' => -1],
                ],
            ],
        ],

        'flex_1' => [
            'label' => 'flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sSingle>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>selectSingle</sheetTitle>
                                        <el>
                                            <select_single_1>
                                                <label>select_single_1 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>select</type>
                                                    <renderType>selectSingle</renderType>
                                                    <items>
                                                        <numIndex index="0">
                                                            <label>foo1</label>
                                                            <value>foo1</value>
                                                        </numIndex>
                                                        <numIndex index="1">
                                                            <label>foo2</label>
                                                            <value>foo2</value>
                                                        </numIndex>
                                                    </items>
                                                </config>
                                            </select_single_1>
                                        </el>
                                    </ROOT>
                                </sSingle>

                                <sCheckbox>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>selectCheckBox</sheetTitle>
                                        <el>
                                            <select_checkxox_1>
                                                <label>select_checkxox_1 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>select</type>
                                                    <renderType>selectCheckBox</renderType>
                                                    <items>
                                                        <numIndex index="0">
                                                            <label>foo1</label>
                                                            <value>1</value>
                                                        </numIndex>
                                                        <numIndex index="1">
                                                            <label>foo 2</label>
                                                            <value>2</value>
                                                        </numIndex>
                                                    </items>
                                                </config>
                                            </select_checkxox_1>
                                        </el>
                                    </ROOT>
                                </sCheckbox>

                                <sTree>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>selectTree</sheetTitle>
                                        <el>
                                            <select_tree_1>
                                                <label>select_tree_1 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>select</type>
                                                    <renderType>selectTree</renderType>
                                                    <foreign_table>pages</foreign_table>
                                                    <size>20</size>
                                                    <maxitems>4</maxitems>
                                                    <treeConfig>
                                                        <expandAll>1</expandAll>
                                                        <parentField>pid</parentField>
                                                        <appearance>
                                                            <showHeader>1</showHeader>
                                                        </appearance>
                                                    </treeConfig>
                                                </config>
                                            </select_tree_1>
                                        </el>
                                    </ROOT>
                                </sTree>

                                <sMultiplesidebyside>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>selectMultipleSideBySide</sheetTitle>
                                        <el>
                                            <select_multiplesidebyside_1>
                                                <label>select_multiplesidebyside_1 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>select</type>
                                                    <renderType>selectMultipleSideBySide</renderType>
                                                    <foreign_table>tx_styleguide_staticdata</foreign_table>
                                                    <size>5</size>
                                                    <autoSizeMax>5</autoSizeMax>
                                                    <minitems>0</minitems>
                                                    <multiSelectFilterItems>
                                                        <numIndex index="0">
                                                            <numIndex index="0"></numIndex>
                                                            <numIndex index="1"></numIndex>
                                                        </numIndex>
                                                        <numIndex index="1">
                                                            <numIndex index="0">foo</numIndex>
                                                            <numIndex index="1">foo</numIndex>
                                                        </numIndex>
                                                        <numIndex index="2">
                                                            <numIndex index="0">bar</numIndex>
                                                            <numIndex index="1">bar</numIndex>
                                                        </numIndex>
                                                    </multiSelectFilterItems>
                                                    <fieldControl>
                                                        <editPopup>
                                                            <renderType>editPopup</renderType>
                                                            <disabled>0</disabled>
                                                        </editPopup>
                                                        <addRecord>
                                                            <renderType>addRecord</renderType>
                                                            <disabled>0</disabled>
                                                            <options>
                                                                <setValue>prepend</setValue>
                                                            </options>
                                                        </addRecord>
                                                        <listModule>
                                                            <renderType>listModule</renderType>
                                                            <disabled>0</disabled>
                                                        </listModule>
                                                    </fieldControl>
                                                </config>
                                            </select_multiplesidebyside_1>
                                            <select_multiplesidebyside_2>
                                                <label>select_multiplesidebyside_2</label>
                                                <description>select_multiplesidebyside_2 foreign_table MM</description>
                                                <config>
                                                    <type>select</type>
                                                    <renderType>selectMultipleSideBySide</renderType>
                                                    <foreign_table>tx_styleguide_staticdata</foreign_table>
                                                    <MM>tx_styleguide_elements_select_flex_1_multiplesidebyside_2_mm</MM>
                                                    <size>5</size>
                                                    <autoSizeMax>5</autoSizeMax>
                                                </config>
                                            </select_multiplesidebyside_2>
                                        </el>
                                    </ROOT>
                                </sMultiplesidebyside>

                                <!-- @todo implement country for flexforms. -->
                                <sCountry>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>Country</sheetTitle>
                                        <el>
                                            <country_1>
                                                <label>Country Basic</label>
                                                <config>
                                                    <type>country</type>
                                                    <labelField>iso2</labelField>
                                                </config>
                                            </country_1>
                                            <country_2>
                                                <label>Country 2</label>
                                                <description>labelField=officialName,prioritizedCountries=AT,CH,sortByOptionLabel</description>
                                                <config>
                                                    <type>country</type>
                                                    <labelField>officialName</labelField>
                                                    <prioritizedCountries>
                                                        <numIndex index="0">AT</numIndex>
                                                        <numIndex index="1">CH</numIndex>
                                                    </prioritizedCountries>
                                                    <default>CH</default>
                                                    <sortItems>
                                                        <label>asc</label>
                                                    </sortItems>
                                                </config>
                                            </country_2>
                                            <country_3>
                                                <label>Country 3</label>
                                                <description>labelField=localizedOfficialName,filter</description>
                                                <config>
                                                    <type>country</type>
                                                    <labelField>localizedOfficialName</labelField>
                                                    <onlyCountries>
                                                        <numIndex index="0">DE</numIndex>
                                                        <numIndex index="1">AT</numIndex>
                                                        <numIndex index="2">CH</numIndex>
                                                        <numIndex index="1">FR</numIndex>
                                                        <numIndex index="3">IT</numIndex>
                                                        <numIndex index="4">HU</numIndex>
                                                        <numIndex index="5">US</numIndex>
                                                        <numIndex index="6">GR</numIndex>
                                                        <numIndex index="7">ES</numIndex>
                                                    </onlyCountries>
                                                    <excludeCountries>
                                                        <numIndex index="0">DE</numIndex>
                                                        <numIndex index="1">ES</numIndex>
                                                    </excludeCountries>
                                                    <sortItems>
                                                        <label>asc</label>
                                                    </sortItems>
                                                </config>
                                            </country_3>
                                        </el>
                                    </ROOT>
                                </sCountry>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],

        'country_1' => [
            'label' => 'Country Basic',
            'config' => [
                'type' => 'country',
                'labelField' => 'iso2',
            ],
        ],
        'country_2' => [
            'label' => 'Country 2',
            'description' => 'labelField=officialName,prioritizedCountries=AT,CH,sortByOptionLabel',
            'config' => [
                'type' => 'country',
                'labelField' => 'officialName',
                'prioritizedCountries' => ['AT', 'CH'],
                'default' => 'CH',
                'sortItems' => [
                    'label' => 'asc',
                ],
            ],
        ],
        'country_3' => [
            'label' => 'Country 3',
            'description' => 'labelField=localizedOfficialName,filter',
            'config' => [
                'type' => 'country',
                'labelField' => 'localizedOfficialName',
                'filter' => [
                    // restrict to the given country ISO2 or ISO3 codes
                    'onlyCountries' => ['DE', 'AT', 'CH', 'FR', 'IT', 'HU', 'US', 'GR', 'ES'],
                    // exclude by the given country ISO2 or ISO3 codes
                    'excludeCountries' => ['DE', 'ES'],
                ],
                'sortItems' => [
                    'label' => 'asc',
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;renderType=selectSingle,
                    select_single_1, select_single_2, select_single_3, select_single_4, select_single_5,
                    select_single_7, select_single_12, select_single_8, select_single_13, select_single_10,
                    select_single_11, select_single_14, select_single_15,select_single_16,select_single_17,
                    select_single_18, select_single_19, select_single_20, select_single_21,
                --div--;renderType=selectSingleBox,
                    select_singlebox_1, select_singlebox_2,select_singlebox_3,
                --div--;renderType=selectCheckBox,
                    select_checkbox_1, select_checkbox_2, select_checkbox_3, select_checkbox_4, select_checkbox_5,
                    select_checkbox_6, select_checkbox_7,
                --div--;renderType=selectMultipleSideBySide,
                    select_multiplesidebyside_1, select_multiplesidebyside_2, select_multiplesidebyside_3,
                    select_multiplesidebyside_5, select_multiplesidebyside_6,
                    select_multiplesidebyside_7, select_multiplesidebyside_8, select_multiplesidebyside_9,
                    select_multiplesidebyside_10,
                --div--;renderType=selectTree,
                    select_tree_1, select_tree_2, select_tree_3, select_tree_4, select_tree_5, select_tree_6,
                --div--;type=category,
                    category_11, category_1n, category_mm,
                --div--;in flex,
                    flex_1,
                --div--;requestUpdate,
                    select_requestUpdate_1,
                 --div--;type=country,
                    country_1,country_2,country_3,
                --div--;meta,
                    sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];
