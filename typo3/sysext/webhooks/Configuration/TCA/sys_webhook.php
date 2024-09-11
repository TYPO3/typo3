<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook',
        'label' => 'name',
        'descriptionColumn' => 'description',
        'crdate' => 'createdon',
        'tstamp' => 'updatedon',
        'adminOnly' => true,
        'hideTable' => true,
        'rootLevel' => 1,
        'groupName' => 'system',
        'default_sortby' => 'name',
        'type' => 'webhook_type',
        'typeicon_column' => 'webhook_type',
        'typeicon_classes' => [
            'default' => 'content-webhook',
        ],
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disabled',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'name, secret',
        'versioningWS_alwaysAllowLiveEdit' => true,
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;config,
                --div--;LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:palette.http_settings,
                --palette--;;http_settings,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;access',
        ],
    ],
    'palettes' => [
        'config' => [
            'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:palette.config',
            'description' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:palette.config.description',
            'showitem' => 'webhook_type, identifier, --linebreak--, name, description, --linebreak--, url, secret',
        ],
        'http_settings' => [
            'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:palette.http_settings',
            'description' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:palette.http_settings.description',
            'showitem' => 'method, verify_ssl, --linebreak--, additional_headers',
        ],
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access',
            'showitem' => 'disabled, starttime, endtime',
        ],
    ],
    'columns' => [
        'webhook_type' => [
            'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.webhook_type',
            'description' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.webhook_type.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'items' => [
                    [
                        'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.webhook_type.select',
                        'value' => '',
                    ],
                ],
                'itemsProcFunc' => \TYPO3\CMS\Webhooks\Tca\ItemsProcFunc\WebhookTypesItemsProcFunc::class . '->getWebhookTypes',
                'dbFieldLength' => 255,
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.name',
            'description' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.name.description',
            'config' => [
                'type' => 'input',
                'required' => true,
                'max' => 100,
                'eval' => 'trim',
            ],
        ],
        'identifier' => [
            'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.identifier',
            'description' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.identifier.description',
            'config' => [
                'type' => 'uuid',
            ],
        ],
        'secret' => [
            'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.secret',
            'description' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.secret.description',
            'config' => [
                'type' => 'password',
                'hashed' => false, // Can't be hashed because it's used to create the signature
                'required' => true,
                'fieldControl' => [
                    'passwordGenerator' => [
                        'renderType' => 'passwordGenerator',
                        'options' => [
                            'title' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.secret.passwordGenerator',
                            'allowEdit' => false,
                            'passwordRules' => [
                                'length' => 40,
                                'random' => 'hex',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'url' => [
            'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.url',
            'description' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.url.description',
            'config' => [
                'type' => 'link',
                'required' => true,
                'allowedTypes' => ['url'],
            ],
        ],
        'method' => [
            'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.method',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'items' => [
                    [
                        'label' => 'POST',
                        'value' => 'POST',
                    ],
                    [
                        'label' => 'GET',
                        'value' => 'GET',
                    ],
                ],
                'dbFieldLength' => 10,
            ],
        ],
        'verify_ssl' => [
            'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.verify_ssl',
            'description' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.verify_ssl.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'additional_headers' => [
            'label' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.additional_headers',
            'description' => 'LLL:EXT:webhooks/Resources/Private/Language/locallang_db.xlf:sys_webhook.additional_headers.description',
            'config' => [
                'type' => 'json',
            ],
        ],
    ],
];
