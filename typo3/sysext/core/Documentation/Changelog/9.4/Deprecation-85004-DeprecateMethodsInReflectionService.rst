.. include:: ../../Includes.txt

============================================================
Deprecation: #85004 - Deprecate methods in ReflectionService
============================================================

See :issue:`85004`

Description
===========

The following methods within :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService` have been marked
as deprecated:

* :php:`getClassTagsValues()`
* :php:`getClassTagValues()`
* :php:`getClassPropertyNames()`
* :php:`hasMethod()`
* :php:`getMethodTagsValues()`
* :php:`getMethodParameters()`
* :php:`getPropertyTagsValues()`
* :php:`getPropertyTagValues()`
* :php:`isClassTaggedWith()`
* :php:`isPropertyTaggedWith()`


Impact
======

Calling any of the deprecated methods above will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with a custom extension trying to gather reflection data via :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService`


Migration
=========

Instead of fetching reflection data via :php:`TYPO3\CMS\Extbase\Reflection\ReflectionService`, the needed data should
directly be fetched from a :php:`TYPO3\CMS\Extbase\Reflection\ClassSchema` instance. An instance can be created by calling
:php:`TYPO3\CMS\Extbase\Reflection\ReflectionService::getClassSchema()`.

.. index:: FullyScanned, PHP-API, ext:extbase
