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
related to retrieving information from `:php:`$GLOBALS['TCA']` have
been deprecated:

* :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::isWebMountRestrictionIgnored()`
* :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::resolveFileReferences()`


Impact
======

Calling one of the following methods raises deprecation level
log errors and will stop working in TYPO3 v15.0.


Affected installations
======================

Instances using the mentioned methods directly.


Migration
=========

isWebMountRestrictionIgnored
----------------------------

.. code-block:: php

    // Before
    return BackendUtility::isWebMountRestrictionIgnored($table);

    // After
    // Retrieve in instance of tcaSchemaFactory with Dependency Injection of TYPO3\CMS\Core\Schema\TcaSchemaFactory
    $schema = $this->tcaSchemaFactory->get('pages');
    return $schema->hasCapability(TcaSchemaCapability::RestrictionWebMount);

resolveFileReferences
---------------------

No substitution is available. Please copy the method to your own codebase and
adjust it to your needs.


..  index:: TCA, FullyScanned, ext:core
