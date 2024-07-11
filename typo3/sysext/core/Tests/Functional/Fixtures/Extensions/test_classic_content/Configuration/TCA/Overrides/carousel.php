<?php

// Add a Content Type called "test_carousel"
$CType = 'test_carousel';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'Carousel',
        'value' => $CType,
        'icon' => 'content-carousel-item-textandimage',
        'group' => 'default',
    ],
    'textmedia',
    'after'
);

// Add a new icon
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$CType] = 'content-carousel-item-textandimage';
// Add the "showitem" type
$GLOBALS['TCA']['tt_content']['types'][$CType] = $GLOBALS['TCA']['tt_content']['types']['header'];

// Add a new relational field for carousel_item DB table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'carousel_items' => [
            'label' => 'Carousel Items',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'test_content_carousel_item',
                'foreign_field' => 'carousel_content_element',
            ],
        ],
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    'carousel_items',
    $CType,
    // after a palette or tab would be "cool"
    'after:header',
);
