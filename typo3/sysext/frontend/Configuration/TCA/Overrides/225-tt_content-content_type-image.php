<?php

defined('TYPO3') or die();

$contentType = 'image';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.image',
        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.image.description',
        'value' => $contentType,
        'icon' => 'content-image',
        'group' => 'default',
    ],
);

$GLOBALS['TCA']['tt_content'] = array_replace_recursive(
    $GLOBALS['TCA']['tt_content'],
    [
        'ctrl' => [
            'typeicon_classes' => [
                $contentType => 'mimetypes-x-content-image',
            ],
        ],
        'types' => [
            $contentType => [
                'showitem' => '
                    --palette--;;headers,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:images,
                        image,
                        --palette--;;mediaAdjustments,
                        --palette--;;gallerySettings,
                        --palette--;;imagelinks,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                        --palette--;;frames,
                        --palette--;;appearanceLinks,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                        categories,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                ',
            ],
        ],
    ]
);
