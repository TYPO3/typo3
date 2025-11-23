..  include:: /Includes.rst.txt

..  _deprecation-106393-1742454612:

========================================================
Deprecation: #106393 - Various methods in BackendUtility
========================================================

See :issue:`106393`

Description
===========

Due to the introduction of the Schema API, several methods of
:php:`\TYPO3\CMS\Backend\Utility\BackendUtility` that retrieve
information from :php:`$GLOBALS['TCA']` have been deprecated:

*   :php:`BackendUtility::getCommonSelectFields()`
*   :php:`BackendUtility::getItemLabel()`
*   :php:`BackendUtility::isTableLocalizable()`
*   :php:`BackendUtility::isTableWorkspaceEnabled()`
*   :php:`BackendUtility::isRootLevelRestrictionIgnored()`
*   :php:`BackendUtility::isWebMountRestrictionIgnored()`
*   :php:`BackendUtility::resolveFileReferences()`

Impact
======

Calling any of the mentioned methods now triggers a deprecation-level log
entry and will stop working in TYPO3 v15.0.

The extension scanner reports usages as a **strong** match.

Affected installations
======================

Instances or extensions that directly call these methods are affected.

Migration
=========

The migration strategy is the same for all cases:
use the corresponding Schema API methods directly in your code.
In most cases, you'll need to inject
:php-short:`\TYPO3\CMS\Core\Schema\TcaSchemaFactory` via dependency injection.

getCommonSelectFields
---------------------

No substitution is available. The method was marked as `@internal` already.
If your code depends on this functionality, copy the method into your own
extension.

getItemLabel
------------

..  code-block:: php

    use TYPO3\CMS\Backend\Utility\BackendUtility;
    use TYPO3\CMS\Core\Schema\TcaSchemaCapability;
    use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

    // Before
    return BackendUtility::getItemLabel('pages', 'title');

    // After (retrieve an instance of TcaSchemaFactory via dependency
    // injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory)
    $schema = $this->schemaFactory->has('pages')
        ? $this->schemaFactory->get('pages')
        : null;
    return $schema !== null && $schema->hasField('title')
        ? $schema->getField('title')->getLabel()
        : null;

isTableLocalizable
------------------

..  code-block:: php

    use TYPO3\CMS\Backend\Utility\BackendUtility;
    use TYPO3\CMS\Core\Schema\TcaSchemaCapability;
    use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

    // Before
    return BackendUtility::isTableLocalizable('pages');

    // After (retrieve an instance of TcaSchemaFactory via dependency
    // injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory)
    return $this->schemaFactory->has('pages')
        && $this->schemaFactory->get('pages')
            ->hasCapability(TcaSchemaCapability::Language);

isTableWorkspaceEnabled
-----------------------

..  code-block:: php

    use TYPO3\CMS\Backend\Utility\BackendUtility;
    use TYPO3\CMS\Core\Schema\TcaSchemaCapability;
    use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

    // Before
    return BackendUtility::isTableWorkspaceEnabled('pages');

    // After (retrieve an instance of TcaSchemaFactory via dependency
    // injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory)
    return $this->schemaFactory->has('pages')
        && $this->schemaFactory->get('pages')
            ->hasCapability(TcaSchemaCapability::Workspace);

isRootLevelRestrictionIgnored
-----------------------------

..  code-block:: php

    use TYPO3\CMS\Backend\Utility\BackendUtility;
    use TYPO3\CMS\Core\Schema\TcaSchemaCapability;
    use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

    // Before
    return BackendUtility::isRootLevelRestrictionIgnored('pages');

    // After (retrieve an instance of TcaSchemaFactory via dependency
    // injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory)
    return $this->schemaFactory->has('pages')
        && $this->schemaFactory->get('pages')
            ->getCapability(
                TcaSchemaCapability::RestrictionRootLevel
            )->shallIgnoreRootLevelRestriction();

isWebMountRestrictionIgnored
----------------------------

..  code-block:: php

    use TYPO3\CMS\Backend\Utility\BackendUtility;
    use TYPO3\CMS\Core\Schema\TcaSchemaCapability;
    use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

    // Before
    return BackendUtility::isWebMountRestrictionIgnored('pages');

    // After (retrieve an instance of TcaSchemaFactory via dependency
    // injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory)
    return $this->schemaFactory->has('pages')
        && $this->schemaFactory->get('pages')
            ->hasCapability(TcaSchemaCapability::RestrictionWebMount);

resolveFileReferences
---------------------

No substitution is available. Copy the method into your own codebase and adapt
it as needed.

..  index:: TCA, FullyScanned, ext:core
