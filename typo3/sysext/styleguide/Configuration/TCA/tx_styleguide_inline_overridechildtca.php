<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline with child overrideChildTca',
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
            'label' => 'inline_1 - overrideChildTca+columnsOverride',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_overridechildtca_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'foreign_match_fields' => [
                    'role' => 'inline_1',
                ],
                'overrideChildTca' => [
                    'types' => [
                        '0' => [
                            'showitem' => 'input_1,',
                            'columnsOverrides' => [
                                'input_1' => [
                                    'config' => [
                                        'enableRichtext' => true,
                                        'richtextConfiguration' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'inline_2' => [
            'label' => 'inline_2 - overrideChildTca+columns',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_overridechildtca_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'foreign_match_fields' => [
                    'role' => 'inline_2',
                ],
                'overrideChildTca' => [
                    'types' => [
                        '0' => [
                            'showitem' => 'input_2,',
                        ],
                    ],
                    'columns' => [
                        'input_2' => [
                            'config' => [
                                'enableRichtext' => true,
                                'richtextConfiguration' => 'full',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 'inline_1,inline_2',
        ],
    ],

];
