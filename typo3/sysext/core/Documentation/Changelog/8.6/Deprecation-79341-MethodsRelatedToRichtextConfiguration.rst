.. include:: ../../Includes.txt

===============================================================
Deprecation: #79341 - Methods related to richtext configuration
===============================================================

See :issue:`79341`

Description
===========

The following methods and method arguments have been deprecated:

* Method :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getSpecConfParametersFromArray()`
* Method :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::RTEsetup()`
* Second argument :php:`$specConf` of :php:`\TYPO3\CMS\Core\Html\RteHtmlParser->RTE_transform()`


Impact
======

Using above methods or arguments trigger deprecation log entries, the according methods will vanish with TYPO3 v9.


Affected Installations
======================

Loaded extensions using one of the above methods.


Migration
=========

If not otherwise possible, class :php:`\TYPO3\CMS\Core\Configuration\Richtext` can be used to fetch richtext configuration.
Be aware this class is marked @internal and is likely to change or vanish in TYPO3 v9 again.

.. index:: Backend, RTE, PHP-API
