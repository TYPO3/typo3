<?php
return [
    'ctrl' => [
        'title' => 'Form engine - static data',
        'label' => 'value_1',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
    ],

    'columns' => [


        'value_1' => [
            'label' => 'value_1',
            'config' => [
                'type' => 'input',
                'size' => 10,
            ],
        ],


    ],


    'types' => [
        '0' => [
            'showitem' => 'value_1',
        ],
    ],


];
