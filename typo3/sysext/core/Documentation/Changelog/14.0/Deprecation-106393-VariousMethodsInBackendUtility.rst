..  include:: /Includes.rst.txt

..  _deprecation-106393-1742454612:

========================================================
Deprecation: #106393 - Various methods in BackendUtility
========================================================

See :issue:`106393`

Description
===========

Due to the use of the Schema API the following methods of
:php:`\TYPO3\CMS\Backend\Utility\BackendUtility` which are
related to retrieving information from :php:`$GLOBALS['TCA']`
have been deprecated:

* :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getCommonSelectFields()`
* :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel()`
* :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable()`
* :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isTableWorkspaceEnabled()`
* :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isRootLevelRestrictionIgnored()`
* :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isWebMountRestrictionIgnored()`
* :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::resolveFileReferences()`

Impact
======

Calling one of the mentioned methods raises a deprecation level
log entry and will stop working in TYPO3 v15.0. The extension
scanner will report usages as **strong** match.


Affected installations
======================

Instances using the mentioned methods directly.


Migration
=========

The migration strategy is generally the same. Use the corresponding Schema API
methods directly in your code. In most cases, you'll need to inject the
:php:`TcaSchemaFactory`, if you haven’t already.

getCommonSelectFields
---------------------

No substitution is available. The method had been declared as `@internal`
anyways. If you rely on this functionality, copy the method to your own
codebase.

getItemLabel
------------

.. code-block:: php

    // Before
    return BackendUtility::getItemLabel('pages', 'title');

    // After (Retrieve in instance of tcaSchemaFactory with Dependency Injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory)
    $schema = $this->schemaFactory->has('pages') ? $this->schemaFactory->get('pages') : null;
    return $schema !== null && $schema->hasField('title') ? $schema->getField(('title')->getLabel() : null;

isTableLocalizable
------------------

.. code-block:: php

    // Before
    return BackendUtility::isTableLocalizable('pages');

    // After (Retrieve in instance of tcaSchemaFactory with Dependency Injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory)
    return $this->schemaFactory->has('pages') && $this->schemaFactory->get('pages')->hasCapability(TcaSchemaCapability::Language);

isTableWorkspaceEnabled
------------------

.. code-block:: php

    // Before
    return BackendUtility::isTableWorkspaceEnabled('pages');

    // After (Retrieve in instance of tcaSchemaFactory with Dependency Injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory)
    return $this->schemaFactory->has('pages') && $this->schemaFactory->get('pages')->hasCapability(TcaSchemaCapability::Workspace);

isRootLevelRestrictionIgnored
-----------------------------

.. code-block:: php

    // Before
    return BackendUtility::isTableLocalizable('pages');

    // After (Retrieve in instance of tcaSchemaFactory with Dependency Injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory)
    return $this->schemaFactory->has('pages') && $this->schemaFactory->get('pages')->getCapability(TcaSchemaCapability::RestrictionRootLevel)->shallIgnoreRootLevelRestriction();

isWebMountRestrictionIgnored
----------------------------

.. code-block:: php

    // Before
    return BackendUtility::isWebMountRestrictionIgnored('pages');

    // After (Retrieve in instance of tcaSchemaFactory with Dependency Injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory)
    return $this->tcaSchemaFactory->has('pages') && $this->tcaSchemaFactory->get('pages')->hasCapability(TcaSchemaCapability::RestrictionWebMount);

resolveFileReferences
---------------------

No substitution is available. Please copy the method to your own codebase and
adjust it to your needs.


..  index:: TCA, FullyScanned, ext:core
