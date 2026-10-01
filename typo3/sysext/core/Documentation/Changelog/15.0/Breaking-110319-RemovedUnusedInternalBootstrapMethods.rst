.. include:: /Includes.rst.txt

.. _breaking-110319-1785315902:

=============================================================
Breaking: #110319 - Removed unused internal Bootstrap methods
=============================================================

See :issue:`110319`

Description
===========

The following methods of :php:`\TYPO3\CMS\Core\Core\Bootstrap` have been
removed:

- :php-short:`\TYPO3\CMS\Core\Core\Bootstrap::baseSetup()`
- :php-short:`\TYPO3\CMS\Core\Core\Bootstrap::createConfigurationManager()`
- :php-short:`\TYPO3\CMS\Core\Core\Bootstrap::populateLocalConfiguration()`

All three were marked :php:`@internal` and are not used by TYPO3 Core
anymore. :php-short:`\TYPO3\CMS\Core\Core\Bootstrap::baseSetup()` only registered the class loading
information in non-Composer mode, which :php-short:`\TYPO3\CMS\Core\Core\Bootstrap::init()` does
itself. The two configuration related methods have been inlined into
:php-short:`\TYPO3\CMS\Core\Core\Bootstrap::init()`, as they consisted of a single statement each.

Impact
======

Calling one of the removed methods results in a fatal PHP error
(:php:`Call to undefined method`).

Affected installations
======================

TYPO3 installations with third-party extensions or custom entry point
scripts that call :php-short:`\TYPO3\CMS\Core\Core\Bootstrap::baseSetup()` or
:php-short:`\TYPO3\CMS\Core\Core\Bootstrap::createConfigurationManager()` directly - a pattern
sometimes used in custom, non-Composer bootstrapping scripts.
:php-short:`\TYPO3\CMS\Core\Core\Bootstrap::populateLocalConfiguration()` was already
:php:`protected` and could not be called from the outside.

The extension scanner reports possible usages.

Migration
=========

Remove calls to :php-short:`\TYPO3\CMS\Core\Core\Bootstrap::baseSetup()`,
:php-short:`\TYPO3\CMS\Core\Core\Bootstrap::init()` performs the equivalent setup internally.

Replace :php-short:`\TYPO3\CMS\Core\Core\Bootstrap::createConfigurationManager()` with a direct
instantiation:

..  code-block:: php

    $configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager();

.. index:: PHP-API, FullyScanned, ext:core
