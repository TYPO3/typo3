..  include:: /Includes.rst.txt

..  _breaking-107047-1751982363:

==================================================================
Breaking: #107047 - Remove pointer field functionality of TCA flex
==================================================================

See :issue:`107047`

Description
===========

One of the main features of TCA is the concept of *record types*. This allows
using a single table for different purposes and in different contexts. The
most well-known examples are the "Page Types" of the :sql:`pages` table and the
"Content Types" of the :sql:`tt_content` table. For every specific type, it is
possible to define which fields to display and to customize their
configuration.

A special case historically has been plugin registration, which for a long
time used the so-called *subtypes* feature of TCA. This was an additional layer
below record types, configured using `subtype_value_field` (commonly
`list_type`), and optionally `subtypes_addlist` and
`subtypes_excludelist` to add or remove fields depending on the selected
subtype.

FlexForms attached to such subtypes were configured using
`ds_pointerField` (typically pointing to `list_type,CType`). This came in
combination with the corresponding `ds` configuration, which was an array
with keys combining the pointer fields, for example:

..  code-block:: php

    'ds_pointerField' => 'list_type,CType',
    'ds' => [
        'news_pi1,list' => 'FILE:EXT:news/Configuration/FlexForm.xml',
        'default' => 'FILE:...'
    ],

Over recent TYPO3 versions, this approach has been deprecated in favor of
using record types exclusively for plugin registration via the `CType` field,
making configuration cleaner and easier to understand.

The special plugin content element (`CType=list`) and the corresponding plugin
subtype field `list_type` have been deprecated in
:ref:`deprecation-105076-1726923626` and removed in
:ref:`breaking-105377-1729513863`.
See also :ref:`important-105538-1730752784` for related information about
:php:`ExtensionUtility::configurePlugin()` and
:php:`ExtensionManagementUtility::addTcaSelectItemGroup()`.

With this change, support for `ds_pointerField` and the multi-entry
`ds` array format has now been removed. The `ds` option now points to
a single FlexForm, either directly or via a `FILE:` reference.

FlexForms must instead be assigned via standard `types` configuration
using `columnsOverrides`.

This also affects the *data structure identifier*, which in the commonly used
`tca` type is the `dataStructureKey`. It is now set to `default` if the
table does not support record types or no record type-specific configuration
exists. Otherwise, the `dataStructureKey` is set to the corresponding
record type value, for example `textpic`.

This change affects the following PSR-14 events:

*   :php-short:`\TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent`
*   :php-short:`\TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent`
*   :php-short:`\TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent`
*   :php-short:`\TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent`

A fallback for TYPO3 v14 resolves comma-separated `dataStructureKey`
values (for example, `list_type,CType`) to `CType`.

To address circular dependencies during schema building,
:php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools` now supports
both TCA Schema objects and raw TCA configuration arrays as input. The
following methods accept a union type :php:`array|TcaSchema` for the new
:php:`$schema` parameter:

*   :php:`getDataStructureIdentifier()`
*   :php:`parseDataStructureByIdentifier()`
*   :php:`cleanFlexFormXML()`

Previously, these methods relied on :php:`$GLOBALS['TCA']` internally, which
caused architectural issues. They now operate directly on the provided schema.

All calls to these methods should provide the :php:`$schema` parameter with
either a :php-short:`\TYPO3\CMS\Core\Schema\TcaSchema` instance or a raw TCA
configuration array. Since data structure resolution can be customized by
extensions, the parameter is not strictly mandatory, but it is strongly
recommended to provide it in most cases. An
:php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaSchemaException`
will be thrown if schema resolution is required but no schema is passed.

This change also enables components like
:php-short:`\TYPO3\CMS\Core\Schema\RelationMapBuilder` to use
:php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools` during schema
building, even when only raw TCA is available.

For further details on the enhanced FlexFormTools functionality, see
:ref:`feature-107047-1751984817`.

The following class has been removed as it is no longer required:

* :php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidCombinedPointerFieldException`

Impact
======

**FlexForm Pointer Field Removal**

Any TCA definition that still uses :php:`ds_pointerField` or a `ds`
array with multiple entries (for example `news_pi1,list`) will no longer work
and might cause rendering errors.

**FlexFormTools Schema Parameter**

All code calling :php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools`
methods (:php:`getDataStructureIdentifier()`,
:php:`parseDataStructureByIdentifier()`, :php:`cleanFlexFormXML()`) must be
updated to provide the required :php:`$schema` parameter.

Affected installations
======================

**FlexForm Pointer Field Removal**

All installations using :php:`ds_pointerField` (as the pointer field
functionality has been removed entirely) or an array-like structure for
`ds` in their TCA field type `flex` configuration.

**FlexFormTools Schema Parameter**

All installations with custom code that directly call
:php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools` methods
without providing the :php:`$schema` parameter.
This includes custom extensions or TYPO3 Core patches using these methods.

A TCA migration automatically converts single-entry `ds` arrays.
Multi-entry definitions require manual migration, as they must be aligned with
the correct record type configuration, which may require additional
configuration changes beforehand.

Example for single-entry migration:

**Before:**

..  code-block:: php
    :caption: Migration of single-entry ds configuration (before)

    'ds' => [
        'default' => '<T3DataStructure>...',
    ],

**After:**

..  code-block:: php
    :caption: Migration of single-entry ds configuration (after)

    'ds' => '<T3DataStructure>...',

Migration
=========

**FlexForm Pointer Field Migration**

**Before:**

..  code-block:: php

    'ds_pointerField' => 'list_type,CType',
    'ds' => [
        'news_pi1,list' => 'FILE:EXT:news/Configuration/FlexForm.xml',
        'default' => '<T3DataStructure>...',
    ],

**After:**

..  code-block:: php

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

If no `columnsOverrides` is defined, the default `ds` value of the
field configuration will be used as before.

**FlexFormTools Schema Parameter Migration**

**Before:**

..  code-block:: php

    use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
    $identifier = $flexFormTools->getDataStructureIdentifier(
        $fieldTca,
        'tt_content',
        'pi_flexform',
        $row
    );

**After:**

..  code-block:: php

    use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
    use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

    // Option 1: Using TCA Schema object (recommended for normal usage)
    $tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
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
