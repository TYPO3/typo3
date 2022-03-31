.. include:: /Includes.rst.txt

========================================================================
Deprecation: #84410 - CodeCompletion renamed to CodeCompletionController
========================================================================

See :issue:`84410`

Description
===========

The PHP class :php:`TYPO3\CMS\T3editor\CodeCompletion` has been renamed to
:php:`TYPO3\CMS\T3editor\Controller\CodeCompletionController`.


Impact
======

The old class name has been registered as class alias and will still work.
Old class name usage however is discouraged and should be avoided, the alias will vanish with core version 10.


Affected Installations
======================

Extensions which use the old class name are affected. The extension scanner will find affected extensions using the old
class name.


Migration
=========

Use new class name instead.

.. index:: Backend, PHP-API, FullyScanned, ext:t3editor
