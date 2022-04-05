<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect',
        'label' => 'source_host',
        'label_alt' => 'source_path',
        'label_alt_force' => true,
        'crdate' => 'createdon',
        'tstamp' => 'updatedon',
        'versioningWS' => false,
        'groupName' => 'system',
        'default_sortby' => 'source_host, source_path',
        'rootLevel' => -1,
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disabled',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_redirect',
        ],
        'searchFields' => 'source_host,source_path,target,target_statuscode',
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general, --palette--;;source, --palette--;;targetdetails, protected, creation_type,
                --div--;LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:tabs.redirectCount, disable_hitcount, hitcount, lasthiton, createdon,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, --palette--;;visibility',
        ],
    ],
    'palettes' => [
        'visibility' => [
            'showitem' => 'disabled, --linebreak--, starttime, endtime',
        ],
        'source' => [
            'showitem' => 'source_host, --linebreak--, source_path, respect_query_parameters, is_regexp',
        ],
        'targetdetails' => [
            'showitem' => 'target, target_statuscode, --linebreak--, force_https, keep_query_parameters',
        ],
    ],
    'columns' => [
        'disabled' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
            ],
        ],
        'source_host' => [
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.source_host',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim,' . \TYPO3\CMS\Redirects\Evaluation\SourceHost::class,
                // items will be extended by local sys_domain records using dataprovider TYPO3\CMS\Redirects\FormDataProvider\ValuePickerItemDataProvider
                'valuePicker' => [
                    'items' => [
                        [   'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_host_global_text',
                            '*',
                        ],
                    ],
                ],
                'default' => '*',
            ],
        ],
        'source_path' => [
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.source_path',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
                'eval' => 'trim',
                'placeholder' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_path.placeholder',
                'max' => 2048,
            ],
        ],
        'force_https' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.force_https.0',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'keep_query_parameters' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.keep_query_parameters.0',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'respect_query_parameters' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.respect_query_parameters.0',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'target' => [
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target',
            'config' => [
                'type' => 'link',
                'required' => true,
                'allowedTypes' => ['page', 'file', 'url', 'record'],
                'appearance' => [
                    'allowedOptions' => ['params', 'rel'],
                ],
            ],
        ],
        'target_statuscode' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.301',
                        301,
                        '',
                        'change',
                    ],
                    [
                        'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.302',
                        302,
                        '',
                        'change',
                    ],
                    [
                        'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.303',
                        303,
                        '',
                        'change',
                    ],
                    [
                        'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.307',
                        307,
                        '',
                        'keep',
                    ],
                    [
                        'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.308',
                        308,
                        '',
                        'keep',
                    ],
                ],
                'itemGroups' => [
                    'keep' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.keep',
                    'change' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.change',
                ],
                'default' => 307,
            ],
        ],
        'hitcount' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.hitcount',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'default' => 0,
                'readOnly' => true,
            ],
            'displayCond' => 'USER:TYPO3\CMS\Redirects\UserFunctions\HitCountDisplayCondition->isEnabled',
        ],
        'lasthiton' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.lasthiton',
            'config' => [
                'type' => 'datetime',
                'readOnly' => true,
            ],
            'displayCond' => 'USER:TYPO3\CMS\Redirects\UserFunctions\HitCountDisplayCondition->isEnabled',
        ],
        'createdon' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.creationDate',
            'config' => [
                'type' => 'datetime',
                'readOnly' => true,
            ],
            'displayCond' => 'USER:TYPO3\CMS\Redirects\UserFunctions\HitCountDisplayCondition->isEnabled',
        ],
        'disable_hitcount' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.hitcountState',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        0 => '',
                        'labelChecked' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
                        'labelUnchecked' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.disabled',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
            'displayCond' => 'USER:TYPO3\CMS\Redirects\UserFunctions\HitCountDisplayCondition->isEnabled',
        ],
        'is_regexp' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.is_regexp',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'protected' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.protected',
            'description' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.protected.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'creation_type' => [
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.creation_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.creation_type.0',
                        0,
                    ],
                    [
                        'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.creation_type.1',
                        1,
                    ],
                ],
                'default' => 1,
            ],
        ],
    ],
];
