<?php

return [
    'ctrl' => [
        'label' => 'identifier',
        'title' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.ctrl.title',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-domain',
        ],
    ],
    'columns' => [
        'identifier' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.identifier',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site.identifier',
            'config' => [
                'type' => 'input',
                'size' => 35,
                'max' => 255,
                // identifier is used as directory name - allow a-z,0-9,_,- as chars only.
                // unique is additionally checked server side
                'eval' => 'required,lower,alphanum_x',
            ],
        ],
        'rootPageId' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.rootPageId',
            'config' => [
                'type' => 'select',
                'readOnly' => true,
                'renderType' => 'selectSingle',
                'foreign_table' => 'pages',
                'foreign_table_where' => ' AND (is_siteroot=1 OR (pid=0 AND doktype IN (1,6,7))) AND l10n_parent = 0 ORDER BY pid, sorting',
            ],
        ],
        'base' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.base',
            'description' => 'LLL:EXT:backend/Resources/Private/Language/siteconfiguration_fieldinformation.xlf:site.base',
            'config' => [
                'type' => 'input',
                'eval' => 'required',
            ],
        ],
        'languages' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.languages',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'site_language',
                'foreign_selector' => 'languageId',
                'foreign_unique' => 'languageId',
                'size' => 4,
                'minitems' => 1,
                'appearance' => [
                    'enabledControls' => [
                        'info' => false,
                    ],
                ],
            ],
        ],
        'errorHandling' => [
            'label' => 'LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.errorHandling',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'site_errorhandling',
                'appearance' => [
                    'enabledControls' => [
                        'info' => false,
                    ],
                ],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'identifier, rootPageId, base,
                --div--;LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.tab.languages, languages,
                --div--;LLL:EXT:backend/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.tab.errorHandling, errorHandling',
        ],
    ],
];
