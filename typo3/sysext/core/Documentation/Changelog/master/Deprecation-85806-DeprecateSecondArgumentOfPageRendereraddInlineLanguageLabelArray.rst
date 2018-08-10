.. include:: ../../Includes.txt

============================================================================================
Deprecation: #85806 - Deprecate second argument of PageRenderer::addInlineLanguageLabelArray
============================================================================================

See :issue:`85806`

Description
===========

The second argument in :php:`TYPO3\CMS\Core\Page\PageRenderer::addInlineLanguageLabelArray()` has been deprecated.

Setting this (optional) argument to :php:`true` must be resolved by using the :php:`TYPO3\CMS\Core\Localization\LanguageService` directly.


Impact
======

Calling the method with an explicitly set second argument will trigger a deprecation message.


Affected Installations
======================

Any TYPO3 installation with a custom extension calling the method above with a second method argument.


Migration
=========

If the second argument was set to :php:`true` before, use the :php:`TYPO3\CMS\Core\Localization\LanguageService`, if the
second parameter was set to :php:`false` before, just remove the second argument.

.. index:: PHP-API, FullyScanned, ext:core