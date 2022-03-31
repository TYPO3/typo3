.. include:: /Includes.rst.txt

==================================================================================
Deprecation: #88569 - Locales::initialize() in favor of regular singleton instance
==================================================================================

See :issue:`88569`

Description
===========

The method :php:`TYPO3\CMS\Core\Localization\Locales::initialize()` has been marked as deprecated.
It was a workaround to re-initialize the Singleton Instance of the PHP class :php:`Locales` for user-defined locales, which were
loaded by an extensions' :file:`ext_localconf.php`.

:php:`Locales` is now initialized only when needed, and not during the early bootstrap process,
making this functionality obsolete, as this is taken care of within the regular constructor.


Impact
======

Calling :php:`TYPO3\CMS\Core\Localization\Locales::initialize()` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with a third-party extension calling :php:`Locales::initialize()` directly.


Migration
=========

Replace the function call by a regular :php:`GeneralUtility::makeInstance(Locales::class);`
or use Dependency Injection (Constructor Injection or ObjectManager) to fetch an instance of
the :php:`Locales` class.

.. index:: PHP-API, FullyScanned
