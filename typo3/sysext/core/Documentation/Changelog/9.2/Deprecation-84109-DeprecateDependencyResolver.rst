.. include:: ../../Includes.txt

==================================================
Deprecation: #84109 - Deprecate DependencyResolver
==================================================

See :issue:`84109`

Description
===========

The class :php:`\TYPO3\CMS\Core\Package\DependencyResolver` has been marked as deprecated as the code as been merged
into :php:`\TYPO3\CMS\Core\Package\PackageManager`.
Additionally the :php:`\TYPO3\CMS\Core\Package\PackageManager` method :php:`injectDependencyResolver` has been marked as
deprecated and the :php:`\TYPO3\CMS\Core\Package\PackageManager` triggers a deprecation warning when
:php:`\TYPO3\CMS\Core\Service\DependencyOrderingService` is not injected through the constructor.

Impact
======

Installations that use :php:`\TYPO3\CMS\Core\Package\DependencyResolver` or create an own
:php:`\TYPO3\CMS\Core\Package\PackageManager` instance will trigger a deprecation warning.


Affected Installations
======================

All installations that use custom extensions that use the :php:`\TYPO3\CMS\Core\Package\DependencyResolver` class or
create an own :php:`\TYPO3\CMS\Core\Package\PackageManager` instance.


Migration
=========

Use :php:`\TYPO3\CMS\Core\Service\DependencyOrderingService` to manually sort packages.
Pass :php:`\TYPO3\CMS\Core\Service\DependencyOrderingService` to the :php:`\TYPO3\CMS\Core\Package\PackageManager`
constructor if a new instance is created.

.. index:: PHP-API, FullyScanned
