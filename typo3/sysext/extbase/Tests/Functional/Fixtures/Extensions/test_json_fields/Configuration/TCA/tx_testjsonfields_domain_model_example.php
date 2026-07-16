<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Example',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        // Mapped to a custom TypeInterface value object in the model, see Domain\Type\JsonValue.
        // The JSON string is stored in a text column: Extbase in v13 does not bind values of
        // type=json columns as-is, Doctrine would encode the already encoded string again.
        'typed_json_field' => [
            'exclude' => true,
            'label' => 'Typed JSON field',
            'config' => [
                'type' => 'text',
                'default' => '[]',
            ],
        ],
        // Mapped to a nullable custom TypeInterface value object in the model, see Domain\Type\JsonValue.
        'nullable_typed_json_field' => [
            'exclude' => true,
            'label' => 'Nullable typed JSON field',
            'config' => [
                'type' => 'text',
                'nullable' => true,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    title,typed_json_field,nullable_typed_json_field,
                --div--;core.form.tabs:access,
                    hidden,
            ',
        ],
    ],
];
