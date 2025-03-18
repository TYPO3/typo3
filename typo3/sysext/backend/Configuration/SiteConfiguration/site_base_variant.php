<?php

return [
    'ctrl' => [
        'label' => 'base',
        'label_alt' => 'condition',
        'label_alt_force' => true,
        'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.ctrl.title',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-domain',
        ],
    ],
    'columns' => [
        'base' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.base',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_base_variant.base',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'placeholder' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.base.placeholder',
            ],
        ],
        'condition' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site_base_variant.condition',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
                'valuePicker' => [
                    'items' => [
                        [ 'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition.applicationContext', 'value' => 'applicationContext == "Production"'],
                        [ 'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition.environmentVariable', 'value' => 'getenv("mycontext") == "production"'],
                    ],
                ],
                'placeholder' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_base_variant.condition.placeholder',
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'base,condition',
        ],
    ],
];
