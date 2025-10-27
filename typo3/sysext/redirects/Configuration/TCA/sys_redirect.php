<?php

use TYPO3\CMS\Redirects\Utility\RedirectConflict;

return [
    'ctrl' => [
        'title' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect',
        'descriptionColumn' => 'description',
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
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;core.form.tabs:general, --palette--;;source, --palette--;;targetdetails, protected, --palette--;;internals,
                --div--;redirects.db:tabs.redirectCount, disable_hitcount, hitcount, lasthiton, createdon,
                --div--;core.form.tabs:access, --palette--;;visibility,
                --div--;core.form.tabs:notes, description',
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
        'internals' => [
            'showitem' => 'creation_type, integrity_status',
        ],
    ],
    'columns' => [
        'source_host' => [
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.source_host',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim,' . \TYPO3\CMS\Redirects\Evaluation\SourceHost::class,
                // items will be extended by local sys_domain records using dataprovider TYPO3\CMS\Redirects\FormDataProvider\ValuePickerItemDataProvider
                'valuePicker' => [
                    'items' => [
                        [   'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_host_global_text',
                            'value' => '*',
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
                        'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.301',
                        'value' => 301,
                        'group' => 'change',
                    ],
                    [
                        'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.302',
                        'value' => 302,
                        'group' => 'change',
                    ],
                    [
                        'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.303',
                        'value' => 303,
                        'group' => 'change',
                    ],
                    [
                        'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.307',
                        'value' => 307,
                        'group' => 'keep',
                    ],
                    [
                        'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.308',
                        'value' => 308,
                        'group' => 'keep',
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
                        'label' => '',
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
            'description' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.creation_type.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.creation_type.0',
                        'value' => 0,
                    ],
                    [
                        'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.creation_type.1',
                        'value' => 1,
                    ],
                ],
                'default' => 1,
                'readOnly' => true,
            ],
        ],
        'integrity_status' => [
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.integrity_status',
            'description' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.integrity_status.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'dbFieldLength' => 180,
                'default' => RedirectConflict::NO_CONFLICT,
                'items' => [
                    [
                        'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.integrity_status.no_conflict',
                        'value' => RedirectConflict::NO_CONFLICT,
                    ],
                    [
                        'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.integrity_status.self_reference',
                        'value' => RedirectConflict::SELF_REFERENCE,
                    ],
                ],
                'readOnly' => true,
            ],
        ],
    ],
];
