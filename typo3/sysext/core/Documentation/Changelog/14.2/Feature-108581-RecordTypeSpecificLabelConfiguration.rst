..  include:: /Includes.rst.txt

..  _feature-108581-1735479000:

===========================================================
Feature: #108581 - Record type specific label configuration
===========================================================

See :issue:`108581`

Description
===========

Previously, the TCA label configuration (:php:`ctrl['label']`, :php:`ctrl['label_alt']`,
and :php:`ctrl['label_alt_force']`) applied globally to all record types within a table.
This meant that all content elements in :sql:`tt_content`, regardless of their
:php:`CType`, displayed the same field(s) as their label in the backend.

It is now possible to define a type-specific label configuration directly in the
TCA :php:`types` section. These settings override the global :php:`ctrl` label
configuration for the respective record type:

*   :php:`label` - Primary field used for the record title
*   :php:`label_alt` - Alternative field(s) used when label is empty (or as additional fields)
*   :php:`label_alt_force` - Force display of alternative fields alongside the primary label

This is especially useful for tables like :sql:`tt_content` where different content
element types may benefit from showing different fields. For example, an "Image"
content element could display the image caption, while a "Text" element shows the
header field.


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

*   All types use :php:`header` as the primary label field (from :php:`ctrl['label']`).
*   The **article** type additionally displays the :php:`teaser` field if :php:`header` is empty.
*   The **event** type displays :php:`header` together with :php:`event_date` and :php:`location`
    (since :php:`label_alt_force` is enabled).

When adding a new record type to an existing table, the label configuration can be
provided as the 3rd argument :php:`$additionalTypeInformation` of
:php-short:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType`.

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tx_my_table.php

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.shortcut',
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

*   Content element tables such as :sql:`tt_content` with different :php:`CType`
    values
*   Tables with distinct record types serving different purposes
*   Plugin records with varying functionality per type
*   Any table where the record type changes the record's purpose or meaning

All places in TYPO3 that use :php-short:`\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle()`
automatically benefit from this feature without any code changes. This includes
record lists, page trees, history views, workspaces, and similar backend modules.

Functionality like FormEngine records cannot profit yet from this option,
as FormEngine does not support Schema API yet.

..  index:: TCA, Backend, ext:core
