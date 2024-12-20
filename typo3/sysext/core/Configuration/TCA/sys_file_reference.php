<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference',
        'label' => 'uid_local',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
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
        'uid_local' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.uid_local',
            'config' => [
                'type' => 'group',
                'size' => 1,
                'relationship' => 'manyToOne',
                'allowed' => 'sys_file',
                'hideSuggest' => true,
            ],
        ],
        'uid_foreign' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.uid_foreign',
            'config' => [
                'type' => 'number',
                'size' => 10,
            ],
        ],
        'tablenames' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.tablenames',
            'config' => [
                // @todo: type=input is probably not a good choice here.
                'type' => 'input',
                'size' => 30,
                'max' => 64,
                'eval' => 'trim',
            ],
        ],
        'fieldname' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.fieldname',
            'config' => [
                // @todo: type=input is probably not a good choice here.
                'type' => 'input',
                'max' => 64,
                'size' => 30,
            ],
        ],
        'sorting_foreign' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.sorting_foreign',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'default' => 0,
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
                'nullable' => true,
                'placeholder' => '__row|uid_local|metadata|title',
                'mode' => 'useOrOverridePlaceholder',
                'default' => null,
            ],
        ],
        'link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.link',
            'config' => [
                'type' => 'link',
                'size' => 20,
                'appearance' => [
                    'browserTitle' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.link',
                ],
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
                'nullable' => true,
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
                'nullable' => true,
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
        \TYPO3\CMS\Core\Resource\FileType::TEXT->value => [
            'showitem' => '
				--palette--;;basicoverlayPalette,
				--palette--;;filePalette',
        ],
        \TYPO3\CMS\Core\Resource\FileType::IMAGE->value => [
            'showitem' => '
				--palette--;;imageoverlayPalette,
				--palette--;;filePalette',
        ],
        \TYPO3\CMS\Core\Resource\FileType::AUDIO->value => [
            'showitem' => '
				--palette--;;audioOverlayPalette,
				--palette--;;filePalette',
        ],
        \TYPO3\CMS\Core\Resource\FileType::VIDEO->value => [
            'showitem' => '
				--palette--;;videoOverlayPalette,
				--palette--;;filePalette',
        ],
        \TYPO3\CMS\Core\Resource\FileType::APPLICATION->value => [
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
        // @internal: Same as basic palette with additional "alternative" and "link" fields.
        // @todo Remove in v14 as breaking change and use basicoverlayPalette instead.
        'extendedBasicOverlayPalette' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.basicoverlayPalette',
            'showitem' => 'alternative,description,--linebreak--,link,title',
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
