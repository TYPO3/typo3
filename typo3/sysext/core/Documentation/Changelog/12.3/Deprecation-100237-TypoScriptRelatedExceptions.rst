.. include:: /Includes.rst.txt

.. _deprecation-100237-1679393509:

====================================================
Deprecation: #100237 - TypoScript-related exceptions
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

The exception :php:`\TYPO3\CMS\Core\Exception\MissingTsfeException` was an internal
communication class and was caught internally, the use case was solved in a more
simple way avoiding the exception.

The exception :php:`\TYPO3\CMS\Core\Configuration\TypoScript\Exception\InvalidTypoScriptConditionException`
was related to conditions which triggered a warning within the symfony expression language. Those were
turned into this exception in TYPO3 v11. In TYPO3 v12, the original exception will bubble up, forcing
developers to fix the broken Symfony condition syntax.


Affected installations
======================

Third-party extensions most likely neither throw nor catch these exceptions, the
extension scanner will find possible usages.


Migration
=========

No direct migration available.

.. note::

    Using the :typoscript:`getTSFE()` function, developers have to ensure
    that "TSFE" is available before accessing its properties. A missing "TSFE",
    e.g. in backend context, does no longer automatically evaluate the whole
    condition to :php:`FALSE`. Instead, the function returns :php:`NULL`,
    which can be checked using either :typoscript:`[getTSFE() && getTSFE().id == 42]`
    or the null-safe operator :typoscript:`[getTSFE()?.id == 42]`.

.. index:: PHP-API, FullyScanned, ext:core
