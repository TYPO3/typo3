.. include:: /Includes.rst.txt

=====================================================
Deprecation: #89001 - Internal public TSFE properties
=====================================================

See :issue:`89001`

Description
===========

The following properties of the :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` class have been marked as deprecated:

* :php:`cHash_array`
* :php:`cHash`
* :php:`domainStartPage`

The properties are now built into proper arguments of the PHP objects
:php:`\TYPO3\CMS\Core\Site\Entity\Site`
and :php:`\TYPO3\CMS\Core\Routing\PageArguments`.

This follows the pattern of not accessing these properties through
the global :php:`TSFE` object directly anymore.


Impact
======

Accessing these properties directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions or TypoScript directly
accessing these properties.


Migration
=========

Use the properties of :php:`Site` and :php:`PageArguments` instead:

* :php:`\TYPO3\CMS\Core\Site\Entity\Site->getRootPageId()` (e.g. via :php:`$request->getAttribute('site')`)
* :php:`\TYPO3\CMS\Core\Routing\PageArguments->getArguments()['cHash']` (e.g. via :php:`$request->getAttribute('routing')`)

Please note that accessing these variables should be avoided via the :php:`TSFE` context.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
