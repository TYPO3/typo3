..  include:: /Includes.rst.txt

..  _deprecation-105230-1728374467:

========================================================================
Deprecation: #105230 - TypoScriptFrontendController and $GLOBALS['TSFE']
========================================================================

See :issue:`105230`

Description
===========

Class :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` and
its global instance :php:`$GLOBALS['TSFE']` have been marked as deprecated.
The class will be removed with TYPO3 v14.


Impact
======

Calling :php:`TypoScriptFrontendController` methods, or accessing state from
:php:`$GLOBALS['TSFE']` is considered deprecated.


Affected installations
======================

Various instances may still retrieve information from :php:`$GLOBALS['TSFE']`.
Remaining uses should be adapted. The extension scanner will find possible
matches.

To keep backwards compatibility in TYPO3 v13, some calls can not raise
deprecation level log messages.


Migration
=========

See :ref:`breaking-102621-1701937690` for details on substitutions. In general,
most state used by extensions has been turned into request attributes.

..  index:: Frontend, PHP-API, FullyScanned, ext:frontend
