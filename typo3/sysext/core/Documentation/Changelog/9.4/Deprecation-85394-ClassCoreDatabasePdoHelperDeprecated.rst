.. include:: /Includes.rst.txt

==============================================================
Deprecation: #85394 - Class Core\Database\PdoHelper deprecated
==============================================================

See :issue:`85394`

Description
===========

The PHP class :php:`TYPO3\CMS\Core\Database\PdoHelper` and its static method
:php:`importSql()` have been marked as deprecated.


Impact
======

Using the method triggers a PHP :php:`E_USER_DEPRECATED` error, the class will be removed in TYPO3 v10.


Affected Installations
======================

Instances with extensions calling :php:`TYPO3\CMS\Core\Database\PdoHelper::importSql()`.
The extension scanner will find affected extensions.


Migration
=========

The method has been of limited use from an extension point of view. If needed by an extension,
the method should be copied over into extension code.

.. index:: Database, PHP-API, FullyScanned
