.. include:: /Includes.rst.txt

=================================================================
Deprecation: #90260 - ResourceFactory::getInstance pseudo-factory
=================================================================

See :issue:`90260`

Description
===========

The method :php:`ResourceFactory::getInstance()` acts as a wrapper
for the constructor which originally was meant as a performance
improvement as pseudo-singleton concept in TYPO3 v4.7.

However, :php:`ResourceFactory` was never optimized and now with Dependency
Injection, :php:`ResourceFactory` can be used directly.

Therefore the method has been marked as deprecated.


Impact
======

Calling :php:`ResourceFactory::getInstance()` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with custom PHP code calling the method.


Migration
=========

Check TYPO3's "Extension Scanner" in the Install Tool if you're affected and replace with constructor injection via Dependency
Injection if possible, or use :php:`GeneralUtility::makeInstance(ResourceFactory::class)` instead.

The latter can already applied in earlier versions (TYPO3 v7 or higher) to ease optimal migration of this deprecation.

.. index:: FAL, PHP-API, FullyScanned, ext:core
