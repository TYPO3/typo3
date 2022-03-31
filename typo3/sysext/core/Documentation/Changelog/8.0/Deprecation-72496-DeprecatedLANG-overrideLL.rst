
.. include:: /Includes.rst.txt

==================================================
Deprecation: #72496 - Deprecated $LANG->overrideLL
==================================================

See :issue:`72496`

Description
===========

The method `LanguageService::overrideLL()` has been marked as deprecated.


Impact
======

Calling this method directly will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 instance using the LanguageService method directly within an extension or third-party code.

.. index:: PHP-API
