..  include:: /Includes.rst.txt

..  _breaking-107047-1751982363:

==================================================================
Breaking: #107047 - Remove pointer field functionality of TCA flex
==================================================================

See :issue:`107047`

Description
===========

One of the main features of TCA are the *record types*. This allows using
a single table for different purposes and in different contexts. The most
well-known examples are the "Page Types" of the :sql:`pages` table and the
"Content Types" of the :sql:`tt_content` table. For every specific type,
it's possible to define which fields to display and customize their
configuration.

A special case historically has been the plugin registration, which for a
long time used the so-called *sub types* feature of TCA. This was an
additional layer below record types, configured using
`subtype_value_field` (commonly `list_type`), and optionally
`subtypes_addlist` and `subtypes_excludelist` to add or remove
fields depending on the selected subtype.

FlexForms attached to such subtypes were configured using
`ds_pointerField` (typically pointing to `list_type,CType`). This
 came in combination with corresponding `ds` configuration, which
 was an array with keys combining the pointer fields, e.g.:

.. code-block:: php

    'ds_pointerField' => 'list_type,CType',
    'ds' => [
        'news_pi1,list' => 'FILE:EXT:news/Configuration/FlexForm.xml',
        'default' => 'FILE:...'
    ],

Over recent TYPO3 versions, this approach has been deprecated in favor
of using record types exclusively for plugin registration via the
`CType` field, making configuration cleaner and easier to understand.

The special plugin content element (`CType=list`) and the corresponding plugin
subtype field `list_type` have been deprecated in :ref:`deprecation-105076-1726923626`
and have been removed in :ref:`breaking-105377-1729513863`. You should also
check corresponding information regarding the usage of :php:`ExtensionUtility::configurePlugin()`
and :php:`ExtensionManagementUtility::addTcaSelectItemGroup()`, see :ref:`important-105538-1730752784`.

With this change, support for `ds_pointerField` and the multi-entry `ds` array
format has now been removed. The `ds` option now points to a single FlexForm,
either directly or via a `FILE:` reference.

FlexForms must instead be assigned via standard `types` configuration
using `columnsOverrides`.

This also affects the "data structure identifier", which in the commonly used
"tca" type the `dataStructureKey`, which is now set to `default` in case the
table does not support record types or no record type specific configuration
exists. Otherwise the `dataStructureKey` is set to the corresponding record
type value, e.g. `textpic`.

This affects the related PSR-14 events:

* :php:`\TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent`
* :php:`\TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent`
* :php:`TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent`
* :php:`TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent`

There is a fallback for v14 in place, resolving a comma-separated `dataStructureKey`,
e.g. `list_type,CType` to `CType`.

To address circular dependencies during schema building, :php:`FlexFormTools`
has been enhanced to support both TCA Schema objects and raw TCA configuration
arrays as input. The following methods now accept a union type :php:`array|TcaSchema`
for the new :php:`$schema` parameter:

* :php:`getDataStructureIdentifier()`
* :php:`parseDataStructureByIdentifier()`
* :php:`cleanFlexFormXML()`

These methods previously relied on :php:`$GLOBALS['TCA']` internally, which caused
architectural issues and is resolved now by working with the given schema directly.

Therefore, all calls to these methods should provide the :php:`$schema` with either
a TcaSchema or raw TCA configuration array. However, since data structure resolution
can be customized by extensions, the :php:`$schema` parameter is not strictly mandatory.
But, it is strongly recommended to provide it in most cases, as an :php:`InvalidTcaSchemaException`
will be thrown if schema resolution is required but no schema is passed.

This change further allows components like :php:`RelationMapBuilder` to use
:php:`FlexFormTools` during schema building processes where only raw TCA is available.

For more details on the enhanced FlexFormTools functionality, see
:ref:`feature-107047-1751984817`.

The following classes have been removed as they serve no further purpose:

* :php:`TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidCombinedPointerFieldException`

Impact
======

**FlexForm Pointer Field Removal**

Any TCA definition that still uses `ds_pointerField` or a `ds`
array with multiple entries (e.g., like `news_pi1,list`) will
no longer work and might cause errors on rendering.

**FlexFormTools Schema Parameter**

All code calling :php:`FlexFormTools` methods (:php:`getDataStructureIdentifier()`,
:php:`parseDataStructureByIdentifier()`, :php:`cleanFlexFormXML()`) must be updated
to provide the required :php:`$schema` parameter.

Affected installations
======================

**FlexForm Pointer Field Removal**

All installations using `ds_pointerField` and array-like structure for `ds` in
their TCA type `flex` configuration.

**FlexFormTools Schema Parameter**

All installations with custom code that directly calls :php:`FlexFormTools`
methods without providing the schema parameter. This includes custom extensions
or TYPO3 Core patches that use these methods directly.

A TcaMigration converts single-entry :php:`ds` arrays automatically. Multi-entry
definitions require manual migration, since they have to be matched with the
correct record type configuration, which might require additional configuration
changes beforehand.

Example for the single-entry migration:

.. code-block:: php

    // before
    'ds' => [
        'default' => '<T3DataStructure>...',
    ],

    // after
    'ds' => '<T3DataStructure>...',

Migration
=========

**FlexForm Pointer Field Migration**

Before:

.. code-block:: php

    'ds_pointerField' => 'list_type,CType',
    'ds' => [
        'news_pi1,list' => 'FILE:EXT:news/Configuration/FlexForm.xml',
        'default' => '<T3DataStructure>...',
    ],

After:

.. code-block:: php

    'columns' => [
        'pi_flexform' => [
            'config' => [
                'ds' => '<T3DataStructure>...',
            ],
        ],
    ],
    'types' => [
        'news_pi1' => [
            'columnsOverrides' => [
                'pi_flexform' => [
                    'config' => [
                        'ds' => 'FILE:EXT:news/Configuration/FlexForm.xml',
                    ],
                ],
            ],
        ],
    ],

If no `columnsOverrides` is defined, the default `ds` value of the field
configuration will be used, as usual behaviour.

**FlexFormTools Schema Parameter Migration**

Before:

.. code-block:: php

    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
    $identifier = $flexFormTools->getDataStructureIdentifier(
        $fieldTca,
        'tt_content',
        'pi_flexform',
        $row
    );

After:

.. code-block:: php

    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

    // Option 1: Using TCA Schema object (recommended for normal usage)
    $tcaSchema = $tcaSchemaFactory->get('tt_content');
    $identifier = $flexFormTools->getDataStructureIdentifier(
        $fieldTca,
        'tt_content',
        'pi_flexform',
        $row,
        $tcaSchema
    );

    // Option 2: Using raw TCA array (for schema building contexts)
    $rawTca = $fullTca['tt_content'];
    $identifier = $flexFormTools->getDataStructureIdentifier(
        $fieldTca,
        'tt_content',
        'pi_flexform',
        $row,
        $rawTca
    );

..  index:: Backend, FlexForm, TCA, PartiallyScanned, ext:core
