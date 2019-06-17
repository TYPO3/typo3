.. include:: ../../Includes.txt

==================================================================================
Deprecation: #88569 - Locales::initialize() in favor of regular singleton instance
==================================================================================

See :issue:`88569`

Description
===========

The method :php:`Locales::initialize()` has been deprecated. It was a workaround to re-initialize
the Singleton Instance of the PHP class `Locales` for user-defined locales, which were
loaded by an extensions' `ext_localconf.php`.

Locales is now initialized only when needed, and not during the early bootstrap process,
making this functionality obsolete, as this is taken care of within the regular constructor.


Impact
======

Calling :php:`Locales::initialize()` will trigger a deprecation notice.


Affected Installations
======================

Any TYPO3 installation with a third-party extension calling `Locales::initialize()` directly.


Migration
=========

Replace the function call by a regular :php:`GeneralUtility::makeInstance(Locales::class);` call
or use Dependency Injection (Constructor Injection or ObjectManager) to fetch an instance of
the Locales class.

.. index:: PHP-API, FullyScanned