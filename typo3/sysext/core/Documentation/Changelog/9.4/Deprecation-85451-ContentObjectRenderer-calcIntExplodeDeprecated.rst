.. include:: /Includes.rst.txt

========================================================================
Deprecation: #85451 - ContentObjectRenderer->calcIntExplode() deprecated
========================================================================

See :issue:`85451`

Description
===========

Method :php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->calcIntExplode()`
has been marked as deprecated and should not be used any longer.


Impact
======

Using the method will trigger a PHP :php:`E_USER_DEPRECATED` error, the method will
be removed in TYPO3 v10.


Affected Installations
======================

The tiny method has been a helper for GMENU rendering and was
most likely only used internally. The extension scanner will
find possible usages within extensions.


Migration
=========

Copy the method to the extension code if needed.

.. index:: Frontend, PHP-API, FullyScanned
