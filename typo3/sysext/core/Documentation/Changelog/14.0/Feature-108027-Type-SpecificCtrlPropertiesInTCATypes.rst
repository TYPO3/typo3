..  include:: /Includes.rst.txt

..  _feature-108027-1762779249:

===========================================================
Feature: #108027 - Type-specific title in TCA types section
===========================================================

See :issue:`108027`

Description
===========

It is now possible to define a type-specific :php:`title` in the TCA
:php:`types` section. This value overrides the global table title defined in
:php:`ctrl['title']` for the respective record type.

This allows different record types of the same table to display different
titles in the TYPO3 backend user interface, making it clearer which kind of
record is being created or displayed.

..  note::

    In addition to :php:`title`, the :php:`previewRenderer` property can also
    be overridden per type. This was already possible through explicit evaluation
    and use of the Schema API, but is now consistently respected by FormEngine
    as well.


Implementation
--------------

This feature has been implemented in two areas to ensure consistent behavior:

1. **Schema API**
   The :php-short:`\TYPO3\CMS\Core\Schema\TcaSchemaFactory` merges
   type-specific configuration into sub-schemas using
   :php:`array_replace_recursive()`. This makes type-specific titles available
   through :php:`$schema->getTitle()`.

2. **FormEngine**
   The data provider
   :php-short:`\TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesCtrlOverrides`
   merges the type-specific :php:`title` and :php:`previewRenderer` into
   :php:`processedTca['ctrl']` during form rendering. This ensures that both
   FormEngine and any legacy code accessing :php:`ctrl` directly will see the
   correct type-specific values.


Example
-------

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/tx_my_table.php

    return [
        'ctrl' => [
            'title' => 'my_extension.db:tx_my_table',
            'type' => 'record_type',
            // ... other ctrl configuration
        ],
        'types' => [
            'article' => [
                'title' => 'my_extension.db:tx_my_table.type.article',
                'showitem' => 'title, content, author',
            ],
            'news' => [
                'title' => 'my_extension.db:tx_my_table.type.news',
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

*   The **article** type displays the title defined in the language file key
    :php:`tx_my_table.type.article`.
*   The **news** type displays the title defined in
    :php:`tx_my_table.type.news`.
*   The **event** type falls back to the global table title from
    :php:`ctrl['title']`.


Impact
======

Tables with multiple record types can now define more specific and descriptive
titles for each type in the backend user interface. This improves usability and
clarity for editors by making it immediately obvious which type of record is
being created or edited.

This feature is especially useful for:

*   Content element tables such as :sql:`tt_content` with different :php:`CType`
    values
*   Tables with distinct record types serving different purposes
*   Plugin records with varying functionality per type
*   Any table where the record type changes the record's purpose or meaning

..  index:: TCA, Backend, ext:core
