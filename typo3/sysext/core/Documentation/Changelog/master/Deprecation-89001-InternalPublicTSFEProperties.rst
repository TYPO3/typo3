.. include:: ../../Includes.txt

=====================================================
Deprecation: #89001 - Internal public TSFE properties
=====================================================

See :issue:`89001`

Description
===========

The following properties of the :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` class have been marked as deprecated:

* cHash_array
* cHash
* domainStartPage

The properties are now built into proper arguments of the PHP objects :php:`Site` and :php:`PageArguments`.

This follows the pattern of not accessing these properties through
the global :php:`TSFE` object directly anymore.


Impact
======

Accessing these properties directly will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions or TypoScript directly
accessing these values.


Migration
=========

Use the properties of :php:`Site` and :php:`PageArguments` instead:

* :php:`Site->getRootPageId()`
* :php:`PageArguments->getArguments()['cHash']

Please note that accessing these variables should be avoided via the TSFE context.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
