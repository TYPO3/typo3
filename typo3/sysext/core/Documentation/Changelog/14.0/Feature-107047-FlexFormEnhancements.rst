..  include:: /Includes.rst.txt

..  _feature-107047-1751984817:

========================================================================================
Feature: #107047 - FlexForm enhancements: Direct plugin registration and raw TCA support
========================================================================================

See :issue:`107047`

FlexForm direct plugin registration
===================================

The methods :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin()`
and :php:`\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin()` have been
extended to accept a FlexForm definition directly via an additional :php:`$flexForm`
argument.

This new argument allows extensions to provide the FlexForm data structure when
registering a plugin. The FlexForm can either be a reference to a FlexForm
XML file (for example, `FILE:EXT:my_extension/Configuration/FlexForm.xml`) or
the XML content itself.

This simplifies configuration and avoids the need to define the FlexForm separately
in TCA.

Examples
========

**Direct FlexForm plugin registration**

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tt_content.php

    ExtensionUtility::registerPlugin(
        'MyExtension',
        'MyPlugin',
        'My Plugin Title',
        'my-extension-icon',
        'plugins',
        'Plugin description',
        'FILE:EXT:my_extension/Configuration/FlexForm.xml'
    );

Alternatively, using :php:`addPlugin()` when not using Extbase:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/tt_content.php

    ExtensionManagementUtility::addPlugin(
        [
            'My Plugin Title',
            'my_plugin',
            'my-extension-icon'
        ],
        'FILE:EXT:my_extension/Configuration/FlexForm.xml'
    );

Internally, this adds the FlexForm definition to the `ds` option of the plugin
via the :php:`columnsOverrides` configuration and also adds the `pi_flexform`
field to the `showitem` list. For more information, see
:ref:`breaking-107047-1751982363`, which describes the migration of the `ds`
option from multi-entry to single-entry.

FlexFormTools schema parameter requirement
==========================================

The service :php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools`
has been refactored to remove its dependency on :php:`$GLOBALS['TCA']`, which caused
architectural issues.

The following methods now support an explicit :php:`$schema` parameter that accepts
either a :php-short:`\TYPO3\CMS\Core\Schema\TcaSchema` object or a raw TCA
configuration array:

*   :php:`getDataStructureIdentifier()`
*   :php:`parseDataStructureByIdentifier()`
*   :php:`cleanFlexFormXML()`

Previously, these methods had no schema parameter and relied on
:php:`$GLOBALS['TCA']` internally, which was problematic during schema building.

Calling code must now explicitly provide schema data, either as:

*   A resolved :php-short:`\TYPO3\CMS\Core\Schema\TcaSchema` object (for normal usage)
*   A raw TCA configuration array (for schema building contexts)

This architectural improvement eliminates circular dependencies and allows
:php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools` to be used
during schema building processes where TCA Schema
objects are not yet available, resolving issues in components such as the
:php-short:`\TYPO3\CMS\Core\Schema\RelationMapBuilder`.

**FlexFormTools with TCA Schema**

..  code-block:: php
    :caption: Example using TCA Schema

    use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

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

..  code-block:: php
    :caption: Example using raw TCA configuration

    use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

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

..  code-block:: php
    :caption: Example usage in RelationMapBuilder

    use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // In RelationMapBuilder - previously not possible
    $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);

    foreach ($tca as $table => $tableConfig) {
        foreach ($tableConfig['columns'] ?? [] as $fieldName => $fieldConfig) {
            if ($fieldConfig['config']['type'] === 'flex') {
                // Can now use raw TCA during schema building
                $dataStructure = $flexFormTools->parseDataStructureByIdentifier(
                    $identifier,
                    $tableConfig // Raw TCA array
                );
            }
        }
    }

Impact
======

**Direct FlexForm plugin registration**

This enhancement simplifies plugin configuration and FlexForm integration, as
FlexForms can now be registered directly with the plugin. The call
:php:`ExtensionManagementUtility::addPiFlexFormValue()` is no longer required.
This method has been deprecated; see :ref:`deprecation-107047-1751984220`.

**FlexFormTools schema support**

The service now automatically detects the input type and uses the appropriate
resolution strategy for both TCA Schema objects and raw TCA arrays. It no longer
relies on :php:`$GLOBALS['TCA']`, allowing direct control over the service and
making it usable during schema building where no TCA Schema is available.

**Technical details**

The service uses PHP union types (:php:`array|TcaSchema`) and automatically routes
to the appropriate internal methods. Both input types produce identical normalized
output, ensuring consistent data structures for all
:php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools` consumers.

..  index:: Backend, FlexForm, TCA, ext:core
