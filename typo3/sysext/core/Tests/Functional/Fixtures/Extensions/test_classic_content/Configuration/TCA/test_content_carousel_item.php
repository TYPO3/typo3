<?php

return [
    'ctrl' => [
        'title' => 'Carousel Item',
        'label' => 'header',
        'label_alt' => 'header_link',
        'label_alt_force' => 1,
        'default_sortby' => 'ORDER BY header',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'languageField' => 'sys_language_uid',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'iconfile' => 'EXT:core/Resources/Public/Icons/T3Icons/content/content-carousel.svg',
        'versioningWS' => true,
    ],
    'columns' => [
        'header' => [
            'label' => 'Header',
            'config' => [
                'type' => 'input',
            ],
        ],
        'header_link' => [
            'label' => 'Header Link',
            'config' => [
                'type' => 'link',
            ],
        ],
        'bodytext' => [
            'label' => 'Bodytext',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'cols' => 40,
                'rows' => 15,
            ],
        ],
        'carousel_content_element' => [
            'label' => 'Content Element',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'image' => [
            'label' => 'Image',
            'config' => [
                'type' => 'file',
                'relationship' => 'manyToOne',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'header, header_link, bodytext, image',
        ],
    ],
    'palettes' => [
        'language' => [
            'showitem' => 'sys_language_uid, l10n_parent',
        ],
    ],
];
