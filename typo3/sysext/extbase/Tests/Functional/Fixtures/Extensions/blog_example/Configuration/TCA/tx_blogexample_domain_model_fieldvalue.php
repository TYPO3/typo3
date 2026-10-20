<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Field value',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
    ],
    'types' => [
        '1' => ['showitem' => 'title, trimmed, limited, summary, serial, quantity, mandatory, code, reference, contact, published, secret, slug, site_slug, label, amount, flag, note, due_date, due_moment, due_day'],
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
            ],
        ],
        'trimmed' => [
            'label' => 'Trimmed',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'limited' => [
            'label' => 'Limited',
            'config' => [
                'type' => 'input',
                'max' => 10,
            ],
        ],
        'summary' => [
            'label' => 'Summary',
            'config' => [
                'type' => 'text',
                'max' => 10,
            ],
        ],
        'serial' => [
            'label' => 'Serial',
            'config' => [
                'type' => 'number',
            ],
        ],
        'quantity' => [
            'label' => 'Quantity',
            'config' => [
                'type' => 'number',
                'range' => [
                    'lower' => 1,
                ],
            ],
        ],
        'mandatory' => [
            'label' => 'Mandatory',
            'config' => [
                'type' => 'input',
                'required' => true,
            ],
        ],
        'code' => [
            'label' => 'Code',
            'config' => [
                'type' => 'input',
                'eval' => 'unique',
            ],
        ],
        'reference' => [
            'label' => 'Reference',
            'config' => [
                'type' => 'input',
                'eval' => 'uniqueInPid',
            ],
        ],
        'contact' => [
            'label' => 'Contact',
            'config' => [
                'type' => 'email',
            ],
        ],
        'published' => [
            'label' => 'Published',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'secret' => [
            'label' => 'Secret',
            'config' => [
                'type' => 'password',
            ],
        ],
        'slug' => [
            'label' => 'Slug',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['title'],
                    'prefixParentPageSlug' => false,
                ],
                'eval' => 'uniqueInPid',
            ],
        ],
        'site_slug' => [
            'label' => 'Site slug',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['title'],
                    'prefixParentPageSlug' => false,
                ],
                'eval' => 'uniqueInSite',
            ],
        ],
        'label' => [
            'label' => 'Label',
            'config' => [
                'type' => 'input',
                'default' => 'unnamed',
            ],
        ],
        'amount' => [
            'label' => 'Amount',
            'config' => [
                'type' => 'number',
                'default' => 7,
            ],
        ],
        'flag' => [
            'label' => 'Flag',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'note' => [
            'label' => 'Note',
            'config' => [
                'type' => 'input',
                'nullable' => true,
            ],
        ],
        'due_date' => [
            'label' => 'Due date',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'date',
            ],
        ],
        'due_moment' => [
            'label' => 'Due moment',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],
        'due_day' => [
            'label' => 'Due day',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
            ],
        ],
    ],
];
