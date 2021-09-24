<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference',
        'label' => 'uid_local',
        'formattedLabel_userFunc' => 'TYPO3\\CMS\\Core\\Resource\\Service\\UserFileInlineLabelService->getInlineLabel',
        'formattedLabel_userFunc_options' => [
            'sys_file' => [
                'title',
                'name',
            ],
        ],
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'type' => 'uid_local:type',
        'hideTable' => true,
        'delete' => 'deleted',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'rootLevel' => -1,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-other-other',
        ],
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'searchFields' => 'title,description,alternative',
    ],
    'columns' => [
        'sys_language_uid' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'sys_file_reference',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'uid_local' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.uid_local',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'size' => 1,
                'eval' => 'int',
                'maxitems' => 1,
                'minitems' => 0,
                'allowed' => 'sys_file',
                'hideSuggest' => true,
            ],
        ],
        'uid_foreign' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.uid_foreign',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
            ],
        ],
        'tablenames' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.tablenames',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'fieldname' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.fieldname',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'sorting_foreign' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.sorting_foreign',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'max' => 4,
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'table_local' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.table_local',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'default' => 'sys_file',
            ],
        ],
        'title' => [
            'l10n_mode' => 'prefixLangTitle',
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'max' => 255,
                'eval' => 'null',
                'placeholder' => '__row|uid_local|metadata|title',
                'mode' => 'useOrOverridePlaceholder',
                'default' => null,
            ],
        ],
        'link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.link',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'size' => 20,
                'max' => 1024,
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.link',
                        ],
                    ],
                ],
                'softref' => 'typolink',
            ],
        ],
        'description' => [
            // This is used for captions in the frontend
            'l10n_mode' => 'prefixLangTitle',
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.description',
            'config' => [
                'type' => 'text',
                'cols' => 20,
                'rows' => 5,
                'eval' => 'null',
                'placeholder' => '__row|uid_local|metadata|description',
                'mode' => 'useOrOverridePlaceholder',
                'default' => null,
            ],
        ],
        'alternative' => [
            'l10n_mode' => 'prefixLangTitle',
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.alternative',
            'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.alternative.description',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'null',
                'placeholder' => '__row|uid_local|metadata|alternative',
                'mode' => 'useOrOverridePlaceholder',
                'default' => null,
            ],
        ],
        'crop' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.crop',
            'config' => [
                'type' => 'imageManipulation',
            ],
        ],
        'autoplay' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.autoplay',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        0 => '',
                        1 => '',
                    ],
                ],
            ],
        ],
    ],
    'types' => [
        // Note that at the moment we define the same fields for every media type.
        // We leave the extensive definition of each type here anyway, to make clear that you can use it to differentiate between the types.
        '0' => [
            'showitem' => '
				--palette--;;basicoverlayPalette,
				--palette--;;filePalette',
        ],
        \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
            'showitem' => '
				--palette--;;basicoverlayPalette,
				--palette--;;filePalette',
        ],
        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
            'showitem' => '
				--palette--;;basicoverlayPalette,
				--palette--;;filePalette',
        ],
        \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
            'showitem' => '
				--palette--;;basicoverlayPalette,
				--palette--;;filePalette',
        ],
        \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
            'showitem' => '
				--palette--;;basicoverlayPalette,
				--palette--;;filePalette',
        ],
        \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
            'showitem' => '
				--palette--;;basicoverlayPalette,
				--palette--;;filePalette',
        ],
    ],
    'palettes' => [
        // Used for basic overlays: having a filelist etc
        'basicoverlayPalette' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.basicoverlayPalette',
            'showitem' => 'title,description',
        ],
        // Used for everything that is an image (because it has a link and an alternative text)
        'imageoverlayPalette' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette',
            'showitem' => '
				alternative,description,--linebreak--,
				link,title,--linebreak--,crop
				',
        ],
        // Used for everything that is a video
        'videoOverlayPalette' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.videoOverlayPalette',
            'showitem' => '
				title,description,--linebreak--,autoplay
				',
        ],
        // Used for everything that is an audio file
        'audioOverlayPalette' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.audioOverlayPalette',
            'showitem' => '
				title,description,--linebreak--,autoplay
				',
        ],
        // File palette, hidden but needs to be included all the time
        'filePalette' => [
            'showitem' => 'uid_local, hidden, sys_language_uid, l10n_parent',
            'isHiddenPalette' => true,
        ],
    ],
];
