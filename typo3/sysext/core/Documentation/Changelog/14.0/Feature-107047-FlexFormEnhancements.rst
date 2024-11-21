..  include:: /Includes.rst.txt

..  _feature-107047-1751984817:

========================================================================================
Feature: #107047 - FlexForm enhancements: Direct plugin registration and raw TCA support
========================================================================================

See :issue:`107047`

FlexForm Direct Plugin Registration
====================================

The methods :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin()`
and :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin()`
have been extended to accept a FlexForm definition directly via an additional
:php:`$flexForm` argument.

This new argument allows extensions to provide the FlexForm data
structure when registering a plugin.

The FlexForm can either be a reference to a FlexForm XML file
(e.g., `FILE:EXT:myext/Configuration/FlexForm.xml`) or the XML content itself.

This simplifies the configuration and avoids the need to define the
FlexForm separately in TCA.

Examples
========

**Direct FlexForm Plugin Registration**

.. code-block:: php

    ExtensionUtility::registerPlugin(
        'MyExtension',
        'MyPlugin',
        'My Plugin Title',
        'my-extension-icon',
        'plugins',
        'Plugin description',
        'FILE:EXT:myext/Configuration/FlexForm.xml'
    );

Alternatively, using :php:`addPlugin()` when not using Extbase:

.. code-block:: php

    ExtensionManagementUtility::addPlugin(
        [
            'My Plugin Title',
            'my_plugin',
            'my-extension-icon'
        ],
        'FILE:EXT:myext/Configuration/FlexForm.xml'
    );

Internally, this adds the FlexForm definition to the `ds` option of the plugin
via the `columnsOverrides` configuration and also adds the `pi_flexform` field
to the `showitem` list. For more information on this, check :ref:`breaking-107047-1751982363`,
which describes the migration of the `ds` option from multi-entry to single-entry.

FlexFormTools Schema Parameter Requirement
===========================================

The :php:`FlexFormTools` service has been fundamentally changed to eliminate
its previous dependency on :php:`$GLOBALS['TCA']`, which caused architectural
issues.

The following methods now support an explicit :php:`$schema` parameter
that accepts either a TcaSchema object or raw TCA configuration array:

* :php:`getDataStructureIdentifier()`
* :php:`parseDataStructureByIdentifier()`
* :php:`cleanFlexFormXML()`

Previously, these methods had no schema parameter and relied on
:php:`$GLOBALS['TCA']` internally, which was problematic for schema
building processes.

Calling code must now explicitly provide the schema data, either as:

- A resolved TcaSchema object (for normal application usage)
- A raw TCA configuration array (for schema building contexts)

This architectural improvement eliminates circular dependencies and allows
:php:`FlexFormTools` to be used during schema building processes where TCA
Schema objects are not yet available, resolving issues in components like the
:php:`RelationMapBuilder`.

**FlexFormTools with TCA Schema**

.. code-block:: php

    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

    // Using TCA Schema object
    $tcaSchema = $tcaSchemaFactory->get('tt_content');
    $identifier = $flexFormTools->getDataStructureIdentifier(
        $fieldTca,
        'tt_content',
        'pi_flexform',
        $row,
        $tcaSchema
    );

**FlexFormTools with raw TCA array**

.. code-block:: php

    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

    // Using raw TCA configuration array
    $rawTca = $fullTca['tt_content'];
    $identifier = $flexFormTools->getDataStructureIdentifier(
        $fieldTca,
        'tt_content',
        'pi_flexform',
        $row,
        $rawTca
    );

**Schema building context**

.. code-block:: php

    // In RelationMapBuilder - previously not possible
    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

    foreach ($tca as $table => $tableConfig) {
        foreach ($tableConfig['columns'] ?? [] as $fieldName => $fieldConfig) {
            if ($fieldConfig['config']['type'] === 'flex') {
                // Can now use raw TCA during schema building
                $dataStructure = $flexFormTools->parseDataStructureByIdentifier(
                    $identifier,
                    $tableConfig  // Raw TCA array
                );
            }
        }
    }

Impact
======

**Direct FlexForm Plugin Registration**

This change allows simplifying configuration of plugins and corresponding
FlexForms, since they can directly be added on registration. This makes
e.g. the usual `ExtensionManagementUtility::addPiFlexFormValue()` call superfluous.
Therefore this method has been deprecated, see:
:ref:`deprecation-107047-1751984220`.

**FlexFormTools Schema support**

The service now automatically detects the input type and uses the appropriate
resolution strategy for both TCA Schema objects and raw TCA arrays. It does
no longer rely on :php:`$GLOBALS['TCA']`. This allows to directly influence
the service. It furthermore allows usage during schema building where no
TCA Schema is available.

**Technical Details**

The service uses PHP's union types (:php:`array|TcaSchema`) and automatically
routes to the appropriate internal methods. Both input types produce identical
normalized output, ensuring that consumers of the :php:`FlexFormTools` service
receive consistent data structures regardless of the used input type.

..  index:: Backend, FlexForm, TCA, ext:core
