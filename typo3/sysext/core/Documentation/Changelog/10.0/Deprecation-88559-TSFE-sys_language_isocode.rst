.. include:: /Includes.rst.txt

=================================================
Deprecation: #88559 - $TSFE->sys_language_isocode
=================================================

See :issue:`88559`

Description
===========

The public property :php:`TypoScriptFrontendController->sys_language_isocode`
has set the equivalent of :php:`TYPO3\CMS\Core\Site\Entity\SiteLanguage->getTwoLetterIsoCode()` since the introduction
of Site Handling in TYPO3 v9.

As all code should switch to Site Handling, this property can be accessed via
the current site language as well, making this property obsolete.

The property has been marked as deprecated.


Impact
======

Setting or fetching this property will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with a third party extension accessing this property,
or via TypoScript :typoscript:`TSFE:sys_language_isocode`.


Migration
=========

Access the property via :php:`SiteLanguage->getTwoLetterIsoCode()`
and :typoscript:`sitelanguage:twoLetterIsoCode` instead.

.. index:: Frontend, PHP-API, FullyScanned
