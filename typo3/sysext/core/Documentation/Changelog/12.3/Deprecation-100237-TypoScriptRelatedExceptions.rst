.. include:: /Includes.rst.txt

.. _deprecation-100237-1679393509:

====================================================
Deprecation: #100237 - TypoScript related Exceptions
====================================================

See :issue:`100237`

Description
===========

Two exception classes related to the TypoScript condition matching logic have been
marked as deprecated in TYPO3 v12 and will be removed in v13:

* :php:`\TYPO3\CMS\Core\Exception\MissingTsfeException`
* :php:`\TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException`


Impact
======

Both exceptions should have been marked :php:`@internal` within the core, but
were not.

Exception :php:`\TYPO3\CMS\Core\Exception\MissingTsfeException` was an internal
communication class and was caught internally, the use case was solved in a more
simple way avoiding the exception.

Exception :php:`\TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException`
was related to conditions which triggered a warning within the symfony expression language. Those were
turned into this exception in TYPO3 v11. With TYPO3 v12, the original exception will bubble up, forcing
developers to fix the broken symfony condition syntax.


Affected installations
======================

Third party extensions most likely neither throw nor catch these exceptions, the
extension scanner will find possible usages.


Migration
=========

No direct migration available.

.. index:: PHP-API, FullyScanned, ext:core
