<?php
return [
    'ctrl' => [
        'title' => 'Form engine elements - select',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
    ],


    'columns' => [


        'hidden' => [
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'Disable',
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'Publish Date',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0'
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'Expiration Date',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],


        'select_single_1' => [
            'exclude' => 1,
            'label' => 'select_single_1 two items, long text',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'foo and this here is very long text that maybe does not really fit into the form in one line.'
                            . ' Ok let us add even more text to see how this looks like if wrapped. Is this enough now? No?'
                            . ' Then let us add some even more useless text here!',
                        1
                    ],
                    ['bar', 'bar'],
                ],
            ],
        ],
        'select_single_2' => [
            'exclude' => 1,
            'label' => 'select_single_2 itemsProcFunc',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['foo', 1],
                    ['bar', 'bar'],
                ],
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeSelect2ItemsProcFunc->itemsProcFunc',
            ],
        ],
        'select_single_3' => [
            'exclude' => 1,
            'label' => 'select_single_3 static values, dividers, foreign_table_where',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Static values', '--div--'],
                    ['static -2', -2],
                    ['static -1', -1],
                    ['DB values', '--div--'],
                ],
                'foreign_table' => 'tx_styleguide_staticdata',
                'foreign_table_where' => 'AND tx_styleguide_staticdata.value_1 LIKE \'%foo%\' ORDER BY uid',
                // @todo: docu of rootLevel says, foreign_table_where is *ignored*, which is NOT true.
                'rootLevel' => 1,
                'foreign_table_prefix' => 'A prefix: ',
            ],
        ],
        'select_single_4' => [
            'exclude' => 1,
            'label' => 'select_single_4 items with icons',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['foo 1', 'foo1', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'],
                    ['foo 2', 'foo2', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'],
                ],
            ],
        ],
        'select_single_5' => [
            'exclude' => 1,
            'label' => 'select_single_5 showIconTable=true, items with icons',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['foo 1', 'foo1', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'],
                    ['foo 2', 'foo2', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'],
                ],
                'showIconTable' => true,
            ],
        ],
        'select_single_6' => [
            'exclude' => 1,
            'label' => 'select_single_6 selicon_cols=3, showIconTable=true',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['foo 1', 'foo1', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'],
                    ['foo 2', 'foo2', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'],
                    ['foo 3', 'foo3', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'],
                    ['foo 4', 'foo4', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'],
                    ['foo 5', 'foo5', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'],
                ],
                'showIconTable' => true,
                'selicon_cols' => 3,
            ],
        ],
        'select_single_7' => [
            'exclude' => 1,
            'label' => 'select_single_7 fileFolder, dummy first entry, showIconTable=true',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'fileFolder' => 'EXT:styleguide/Resources/Public/Icons',
                'fileFolder_extList' => 'svg',
                'fileFolder_recursions' => 1,
                'showIconTable' => true,
            ],
        ],
        'select_single_8' => [
            'exclude' => 1,
            'label' => 'select_single_8 drop down with empty div',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['First div with items', '--div--'],
                    ['item 1', 1],
                    ['item 2', 2],
                    ['Second div without items', '--div--'],
                    ['Third div with items', '--div--'],
                    ['item 3', 3],
                ],
            ],
        ],
        'select_single_9' => [
            'exclude' => 1,
            'label' => 'select_single_9 wizard slider',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['foo 1', 1],
                    ['foo 2', 2],
                    ['foo 3', 4],
                    ['foo 4', 7],
                    ['foo 5', 8],
                    ['foo 6', 11],
                ],
                'default' => 4,
                'wizards' => [
                    'angle' => [
                        'type' => 'slider',
                        'step' => 1,
                        'width' => 200,
                    ],
                ],
            ],
        ],
        // @todo: selectSingle with size > 1 overlaps with selectSingleBox, except that only one item can be selected
        'select_single_10' => [
            'exclude' => 1,
            'label' => 'select_single_10 size=6, three options',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['foo 1', 1],
                    ['foo 2', 2],
                    ['a divider', '--div--'],
                    ['foo 3', 3],
                ],
                'size' => 6,
            ],
        ],
        'select_single_11' => [
            'exclude' => 1,
            'label' => 'select_single_11 size=2, two options',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['foo 1', 1],
                    ['foo 2', 2],
                ],
                'size' => 2,
            ],
        ],


        'select_singlebox_1' => [
            'exclude' => 1,
            'label' => 'select_singlebox_1',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'items' => [
                    ['foo 1', 1],
                    ['foo 2', 2],
                    ['divider', '--div--'],
                    ['foo 3', 3],
                    ['foo 4', 4],
                ],
            ],
        ],


        'select_checkbox_1' => [
            'exclude' => 1,
            'label' => 'select_checkbox_1',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['foo 1', 1],
                    ['foo 2', 2],
                    ['foo 3', 3],
                ],
            ],
        ],
        'select_checkbox_2' => [
            'exclude' => 1,
            'label' => 'select_checkbox_2 icons, description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['foo 1', 1, '', 'optional description'],
                    ['foo 2', 2, 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg', 'description'],
                    ['foo 3', 3, 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'],
                    ['foo 4', 4],
                ],
            ],
        ],


        'select_multiplesidebyside_1' => [
            'exclude' => 1,
            'label' => 'select_multiplesidebyside_1 autoSizeMax=5, size=3',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['foo 1', 1],
                    ['foo 2', 2],
                    // @todo: divider needs better styling?
                    ['a divider', '--div--'],
                    ['foo 3', 3],
                    ['foo 4', 4],
                    ['foo 5', 5],
                    ['foo 6', 6],
                ],
                // @todo: inconsistent: maxitems must be set to allow multi selection, similar with type=group
                'maxitems' => 999,
                // @todo: size and autoSizeMax behave weird, at least lower bounds should be documented?
                'size' => 3,
                'autoSizeMax' => 5,
                // @todo: multiple does not seem to have any effect at all? Can be commented without change.
                'multiple' => true,
            ],
        ],
        'select_multiplesidebyside_2' => [
            'exclude' => 1,
            'label' => 'select_multiplesidebyside_2 exclusiveKeys=1,2',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['two exclusive items', '--div--'],
                    ['foo 1', 1],
                    ['foo 2', 2],
                    ['casual multiple items', '--div--'],
                    ['foo 3', 3],
                    ['foo 4', 4],
                    ['foo 5', 5],
                    ['foo 6', 6],
                ],
                // @todo: exclusiveKeys without maxitems > 1 doesn't make sense
                'maxitems' => 999,
                // @todo: multiple does not seem to have any effect at all? Can be commented without change.
                'multiple' => true,
                'exclusiveKeys' => '1,2',
            ],
        ],
        'select_multiplesidebyside_3' => [
            'exclude' => 1,
            'label' => 'select_multiplesidebyside_3 itemListStyle, selectedListStyle',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['foo 1', 1],
                    ['foo 2', 2],
                    ['foo 3', 3],
                ],
                'maxitems' => 999,
                'itemListStyle' => 'width:250px;background-color:#ffcccc;',
                'selectedListStyle' => 'width:250px;background-color:#ccffcc;',
                'size' => 2,
            ],
        ],
        'select_multiplesidebyside_4' => [
            'exclude' => 1,
            'label' => 'select_multiplesidebyside_4 enableMultiSelectFilterTextfield=true',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['foo 1', 1],
                    ['foo 2', 2],
                    ['foo 3', 3],
                    ['bar', 4],
                ],
                'maxitems' => 999,
                'enableMultiSelectFilterTextfield' => true,
            ],
        ],
        'select_multiplesidebyside_5' => [
            'exclude' => 1,
            'label' => 'select_multiplesidebyside_5 multiSelectFilterItems, enableMultiSelectFilterTextfield=true',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['foo 1', 1],
                    ['foo 2', 2],
                    ['foo 3', 3],
                    ['bar', 4],
                ],
                'maxitems' => 999,
                'enableMultiSelectFilterTextfield' => true,
                'multiSelectFilterItems' => [
                    ['', ''],
                    ['foo', 'foo'],
                    ['bar', 'bar'],
                ],
            ],
        ],
        'select_multiplesidebyside_6' => [
            'exclude' => 1,
            'label' => 'select_multiplesidebyside_6 wizards',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_styleguide_staticdata',
                'rootLevel' => 1,
                'size' => 5,
                'autoSizeMax' => 20,
                'maxitems' => 999,
                'wizards' => [
                    '_VERTICAL' => 1,
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'edit',
                        'module' => [
                            'name' => 'wizard_edit',
                        ],
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_edit.gif',
                        'popup_onlyOpenIfSelected' => 1,
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ],
                    'add' => [
                        'type' => 'script',
                        'title' => 'add',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_add.gif',
                        'module' => [
                            'name' => 'wizard_add',
                        ],
                        'params' => [
                            'table' => 'tx_styleguide_staticdata',
                            'pid' => '0',
                            'setValue' => 'prepend',
                        ],
                    ],
                    'list' => [
                        'type' => 'script',
                        'title' => 'list',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_list.gif',
                        'module' => [
                            'name' => 'wizard_list',
                        ],
                        'params' => [
                            'table' => 'tx_styleguide_staticdata',
                            'pid' => '0',
                        ],
                    ],
                ],
            ],
        ],


        'select_tree_1' => [
            'exclude' => 1,
            'label' => 'select_tree_1 pages',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'size' => 20,
                // @todo: *must* be set, otherwise invalid upon checking first item?!
                'maxitems' => 4,
                'treeConfig' => [
                    'expandAll' => true,
                    'parentField' => 'pid',
                    'appearance' => [
                        'showHeader' => true,
                    ],
                ],
            ],
        ],
        'select_tree_2' => [
            'exclude' => 1,
            'label' => 'select_tree_2 pages, showHeader=false, nonSelectableLevels=0,1, allowRecursiveMode=true, width=400',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                // @todo: *must* be set, otherwise invalid upon checking first item?!
                'maxitems' => 4,
                'size' => 10,
                'treeConfig' => [
                    'expandAll' => true,
                    'parentField' => 'pid',
                    'appearance' => [
                        'showHeader' => false,
                        'nonSelectableLevels' => '0,1',
                        'allowRecursiveMode' => true, // @TODO: No effect?
                        'width' => 400,
                    ],
                ],
            ],
        ],
        'select_tree_3' => [
            'exclude' => 1,
            'label' => 'select_tree_31 pages, maxLevels=1',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'size' => 20,
                // @todo: *must* be set, otherwise invalid upon checking first item?!
                'maxitems' => 4,
                'treeConfig' => [
                    'expandAll' => true,
                    'parentField' => 'pid',
                    'appearance' => [
                        'showHeader' => true,
                        'maxLevels' => 1,
                    ],
                ],
            ],
        ],
        'select_tree_4' => [
            'exclude' => 1,
            'label' => 'select_tree_4 pages, maxLevels=2',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'size' => 20,
                // @todo: *must* be set, otherwise invalid upon checking first item?!
                'maxitems' => 4,
                'treeConfig' => [
                    'expandAll' => true,
                    'parentField' => 'pid',
                    'appearance' => [
                        'showHeader' => true,
                        'maxLevels' => 2,
                    ],
                ],
            ],
        ],


    ],


    'types' => [
        '0' => [
            'showitem' => '
                --div--;renderType=selectSingle,
                    select_single_1, select_single_2, select_single_3, select_single_4, select_single_5,
                    select_single_6, select_single_7, select_single_8, select_single_9, select_single_10,
                    select_single_11,
                --div--;renderType=selectSingleBox,
                    select_singlebox_1,
                --div--;renderType=selectCheckBox,
                    select_checkbox_1, select_checkbox_2,
                --div--;renderType=selectMultipleSideBySide,
                    select_multiplesidebyside_1, select_multiplesidebyside_2, select_multiplesidebyside_3,
                    select_multiplesidebyside_4, select_multiplesidebyside_5, select_multiplesidebyside_6,
                --div--;renderType=selectTree,
                    select_tree_1, select_tree_2, select_tree_3, select_tree_4,
            ',
        ],
    ],


];
