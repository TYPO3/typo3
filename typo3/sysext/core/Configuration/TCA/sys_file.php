<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'type' => 'type',
        'hideTable' => true,
        'rootLevel' => 1,
        'default_sortby' => 'name ASC',
        'typeicon_column' => 'type',
        'typeicon_classes' => [
            \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => 'mimetypes-text-text',
            \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => 'mimetypes-media-image',
            \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => 'mimetypes-media-audio',
            \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => 'mimetypes-media-video',
            \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => 'mimetypes-application',
            'default' => 'mimetypes-other-other',
        ],
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'searchFields' => 'name, type, mime_type',
    ],
    'columns' => [
        'fileinfo' => [
            'config' => [
                'type' => 'none',
                'renderType' => 'fileInfo',
            ],
        ],
        'storage' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.storage',
            'config' => [
                'readOnly' => true,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'sys_file_storage',
                'maxitems' => 1,
            ],
        ],
        'identifier' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.identifier',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.name',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'type' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.type',
            'config' => [
                'readOnly' => true,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.type.unknown', 'value' => \TYPO3\CMS\Core\Resource\File::FILETYPE_UNKNOWN],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.type.text', 'value' => \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.type.image', 'value' => \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.type.audio', 'value' => \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.type.video', 'value' => \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.type.software', 'value' => \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION],
                ],
            ],
        ],
        'mime_type' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.mime_type',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'sha1' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.sha1',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'size' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.size',
            'config' => [
                'readOnly' => true,
                'type' => 'number',
                'size' => 8,
                'default' => 0,
            ],
        ],
        'missing' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.missing',
            'config' => [
                'readOnly' => true,
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'metadata' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.metadata',
            'config' => [
                'readOnly' => true,
                'type' => 'inline',
                'foreign_table' => 'sys_file_metadata',
                'foreign_field' => 'file',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'fileinfo, storage, missing'],
    ],
    'palettes' => [],
];
