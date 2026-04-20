..  include:: /Includes.rst.txt

..  _feature-108581-1735479000:

===========================================================
Feature: #108581 - Record type specific label configuration
===========================================================

See :issue:`108581`

Description
===========

Previously, the TCA label configuration (`ctrl['label']`, `ctrl['label_alt']`,
and `ctrl['label_alt_force']`) applied globally to all record types in a
table. This meant that all content elements in :sql:`tt_content`, regardless of
their `CType`, displayed the same field(s) as their label in the backend.

It is now possible to define type-specific label configuration in the
TCA `types` section. These settings override global `ctrl` label
configuration for a record type:

*   `label` - Primary field used for the record title
*   `label_alt` - Alternative field(s) used when label is empty (or as
    additional fields)
*   `label_alt_force` - Force display of alternative fields alongside the
    primary label

This is especially useful for tables like :sql:`tt_content` where different
content element types may benefit from showing different fields. For example, an
"Image" content element could display the image caption, while a "Text" element
would show the header field.

Examples
--------

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/tx_my_table.php

    return [
        'ctrl' => [
            'label' => 'header',
            'type' => 'record_type',
            // ... other ctrl configuration
        ],
        'types' => [
            'article' => [
                'label_alt' => 'teaser',
            ],
            'event' => [
                'label_alt' => 'event_date,location',
                'label_alt_force' => true,
            ],
        ],
        // ... columns configuration
    ];

In this example:

*   All types use `header` as the primary label field (from `ctrl['label']`).
*   The `article` type displays the `teaser` field if `header` is
    empty.
*   The `event` type displays `header` together with `event_date` and
    `location` (since `label_alt_force` is enabled).

When adding a new record type to a table, label configuration can
be provided as the third argument `$additionalTypeInformation` of
:php-short:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType`.

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tx_my_table.php

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:frontend.ttc:CType.shortcut',
            'value' => 'my-type',
            'icon' => 'my-icon',
            'group' => 'special',
        ],
        'my-header',
        [
            'label' => 'header',
            'label_alt' => 'records',
        ]
    );

Impact
======

Tables with multiple record types can now define more specific and descriptive
labels for each type in the backend user interface. This improves usability and
clarity for editors by making it immediately obvious which type of record is
being displayed.

This feature is especially useful for:

*   Content element tables such as :sql:`tt_content` with different `CType`
    values
*   Tables with different record types serving different purposes
*   Plugin records with varying functionality for each type
*   Any table where the record type changes the record's purpose or meaning

All occurrences of
:php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle()` in TYPO3
automatically benefit from this feature without any code changes. This includes
record lists, page trees, history views, workspaces, and similar backend
modules.

Functionality such as FormEngine records cannot benefit from this option yet,
as FormEngine does not support the Schema API yet.

..  index:: TCA, Backend, ext:core
