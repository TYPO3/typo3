.. include:: ../../Includes.txt

======================================
Deprecation: #85821 - boostrap methods
======================================

See :issue:`85821`

Description
===========

The following methods of :php:`TYPO3\CMS\Core\Core\Boostrap` have been marked as deprecated. Some of
them will just change their visibility from public to protected in TYPO3 v10 and thus should not
be called externally any longer:

* :php:`TYPO3\CMS\Core\Core\Boostrap::usesComposerClassLoading()`
* :php:`TYPO3\CMS\Core\Core\Boostrap::getInstance()`
* :php:`TYPO3\CMS\Core\Core\Boostrap->configure()`
* :php:`TYPO3\CMS\Core\Core\Boostrap::checkIfEssentialConfigurationExists()`
* :php:`TYPO3\CMS\Core\Core\Boostrap->setEarlyInstance()`
* :php:`TYPO3\CMS\Core\Core\Boostrap->getEarlyInstance()`
* :php:`TYPO3\CMS\Core\Core\Boostrap->getEarlyInstances()`
* :php:`TYPO3\CMS\Core\Core\Boostrap::loadConfigurationAndInitialize()`
* :php:`TYPO3\CMS\Core\Core\Boostrap->initializePackageManagement()`
* :php:`TYPO3\CMS\Core\Core\Boostrap::populateLocalConfiguration()`
* :php:`TYPO3\CMS\Core\Core\Boostrap::disableCoreCache()`
* :php:`TYPO3\CMS\Core\Core\Boostrap::initializeCachingFramework()`
* :php:`TYPO3\CMS\Core\Core\Boostrap->setRequestType()`
* :php:`TYPO3\CMS\Core\Core\Boostrap::setFinalCachingFrameworkCacheConfiguration()`


Impact
======

This deprecation is only interesting for code that interferes with early core boostrap.
Those may trigger PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances using early boostrap code may be affected by this. Those should strive for
using the general entry method :php:`Bootstrap::init()` instead.


Migration
=========

See changes on the typo3/testing-framework which formerly used early instance
bootstrap calls for an example on how existing code can be refactored to use
the top level :php:`Bootstrap::init()` instead.

.. index:: PHP-API, FullyScanned
