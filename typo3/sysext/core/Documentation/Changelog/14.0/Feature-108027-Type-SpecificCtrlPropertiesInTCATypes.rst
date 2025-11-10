..  include:: /Includes.rst.txt

..  _feature-108027-1762779249:

===========================================================
Feature: #108027 - Type-specific title in TCA types section
===========================================================

See :issue:`108027`

Description
===========

It is now possible to define a type-specific :php:`title` in the TCA
:php:`types` section, which overrides the global table title defined in
:php:`ctrl['title']` for that specific record type.

This allows different record types of the same table to display different
titles in the TYPO3 backend UI, making it clearer which type of record is
being created or displayed.

..  note::

    In addition to :php:`title`, the :php:`previewRenderer` property can also
    be overridden per type. This was already possible through explicit evaluation
    and Schema API usage, but is now consistently respected in FormEngine as well.

Implementation
--------------

This feature is implemented in two places to ensure consistent behavior:

1. **Schema API** (:php:`TcaSchemaFactory`): Type-specific configuration is merged
   into sub-schemas via :php:`array_replace_recursive()`, making type-specific
   titles available when using :php:`$schema->getTitle()`.

2. **FormEngine** (:php:`TcaTypesCtrlOverrides` data provider): The type-specific
   :php:`title` (and :php:`previewRenderer`) is merged into :php:`processedTca['ctrl']`
   during form rendering, ensuring FormEngine and legacy code that access ctrl
   directly see the type-specific values.

Example
-------

..  code-block:: php

    return [
        'ctrl' => [
            'title' => 'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:tx_myext_table',
            'type' => 'record_type',
            // ... other ctrl configuration
        ],
        'types' => [
            'article' => [
                'title' => 'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:tx_myext_table.type.article',
                'showitem' => 'title, content, author',
            ],
            'news' => [
                'title' => 'LLL:EXT:myext/Resources/Private/Language/locallang.xlf:tx_myext_table.type.news',
                'showitem' => 'title, content, publish_date',
            ],
            'event' => [
                // No type-specific title - will use the global ctrl['title']
                'showitem' => 'title, content, event_date',
            ],
        ],
        // ... columns configuration
    ];

In this example:

- The "article" type will display with the title defined in the language file
  key :php:`tx_myext_table.type.article`
- The "news" type will display with the title defined in the language file
  key :php:`tx_myext_table.type.news`
- The "event" type will display with the global table title from
  :php:`tx_myext_table`

Impact
======

Tables with multiple record types can now provide more specific and descriptive
titles for each type in the backend UI. This improves the user experience by
making it clearer which type of record is being created or displayed.

This is particularly useful for:

- Content element tables like :sql:`tt_content` with different CTypes
- Tables with distinct record types that serve different purposes
- Plugin records with different functionality per type
- Any table where the record type significantly changes the record's purpose

..  index:: TCA, Backend, ext:core
