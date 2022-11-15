.. include:: /Includes.rst.txt

.. _deprecation-97536-1651523804:

=======================================
Deprecation: #97536 - LinkResultFactory
=======================================

See :issue:`97536`

Description
===========

The PHP class :php:`TYPO3\CMS\Frontend\Typolink\LinkResultFactory` has been
marked as deprecated, as its functionality has been migrated into
:php:`TYPO3\CMS\Frontend\Typolink\LinkFactory`.

In addition, the method :php:`createFromUriString()` has been marked as
deprecated as the shortened variant `createUri()` should be used instead.


Impact
======

Instantiating an object of type :php:`LinkResultFactory` will instantiate
:php:`LinkFactory` instead via class alias in TYPO3 v12, as the class itself
has been removed.

Calling :php:`createFromUriString()` will trigger a deprecation log entry.

The extension scanner reports affected extensions.

Affected installations
======================

TYPO3 installations with custom extensions instantiating :php:`LinkResultFactory`
as a PHP object or calling :php:`createFromUriString()` directly, which is very
rare.


Migration
=========

TYPO3 extensions should migrate to using :php:`LinkFactory` and its main methods
directly.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
