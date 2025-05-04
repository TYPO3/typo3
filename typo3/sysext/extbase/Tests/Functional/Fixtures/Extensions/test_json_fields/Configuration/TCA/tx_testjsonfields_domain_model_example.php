<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_json_fields/Resources/Private/Language/locallang_db.xlf:tx_testjsonfields_domain_model_example',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'iconfile' => 'EXT:test_json_fields/Resources/Public/Icons/icon_tx_testjsonfields_domain_model_example.gif',
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:test_json_fields/Resources/Private/Language/locallang_db.xlf:tx_testjsonfields_domain_model_example.title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_json_fields/Resources/Private/Language/locallang_db.xlf:tx_testjsonfields_domain_model_example.description',
            'config' => [
                'type' => 'text',
                'rows' => 30,
                'cols' => 80,
            ],
        ],
        // This configuration is added to test only in context of extbase and not FormEngine.
        'native_json_as_text_field' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_json_fields/Resources/Private/Language/locallang_db.xlf:tx_testjsonfields_domain_model_example.native_json_as_textfield',
            'config' => [
                'type' => 'text',
                'required' => true,
                'rows' => 30,
                'cols' => 80,
                'default' => '{}',
            ],
        ],
        'tca_json_field' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_json_fields/Resources/Private/Language/locallang_db.xlf:tx_testjsonfields_domain_model_example.tca_json_field',
            'config' => [
                'type' => 'json',
                'default' => '{}',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    title,native_json_as_text_field,tca_json_field,
                --div--;core.form.tabs:access,
                    hidden,
                --div--;core.form.tabs:notes,
                    description,
                --div--;core.form.tabs:extended,
            ',
        ],
    ],
];
