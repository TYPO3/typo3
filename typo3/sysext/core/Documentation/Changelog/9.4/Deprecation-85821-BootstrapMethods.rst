.. include:: ../../Includes.txt

=======================================
Deprecation: #85821 - bootstrap methods
=======================================

See :issue:`85821`

Description
===========

The following methods of :php:`TYPO3\CMS\Core\Core\Bootstrap` have been marked as deprecated. Some of
them will just change their visibility from public to protected in TYPO3 v10 and thus should not
be called externally any longer:

* :php:`TYPO3\CMS\Core\Core\Bootstrap::usesComposerClassLoading()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap::getInstance()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->configure()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap::checkIfEssentialConfigurationExists()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->setEarlyInstance()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->getEarlyInstance()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->getEarlyInstances()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap::loadConfigurationAndInitialize()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->initializePackageManagement()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap::populateLocalConfiguration()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap::disableCoreCache()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap::initializeCachingFramework()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap->setRequestType()`
* :php:`TYPO3\CMS\Core\Core\Bootstrap::setFinalCachingFrameworkCacheConfiguration()`


Impact
======

This deprecation is only interesting for code that interferes with early core bootstrap.
Those may trigger PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances using early bootstrap code may be affected by this. Those should strive for
using the general entry method :php:`Bootstrap::init()` instead.


Migration
=========

See changes on the typo3/testing-framework which formerly used early instance
bootstrap calls for an example on how existing code can be refactored to use
the top level :php:`Bootstrap::init()` instead.

.. index:: PHP-API, FullyScanned
