
.. include:: /Includes.rst.txt

================================================
Breaking: #72686 - Removed RteHtmlParser methods
================================================

See :issue:`72686`

Description
===========

The following methods within `RteHtmlParser` have been removed without substitution:

* `RteHtmlParser->siteUrl()`
* `RteHtmlParser->getUrl()`

The second method parameter of the following methods have been removed as they have no effect anymore:

* `RteHtmlParser->HTMLcleaner_db()`
* `RteHtmlParser->getKeepTags()`


Impact
======

Calling either `RteHtmlParser->siteUrl()` or `RteHtmlParser->getUrl()` will result in a PHP fatal error.

Calling `RteHtmlParser->HTMLcleaner_db()` or `RteHtmlParser->getKeepTags()` with a second parameter will have no effect anymore.


Affected Installations
======================

TYPO3 instances which use RteHtmlParser methods directly within a third-party extension for HTML transformation.


Migration
=========

Use `GeneralUtility::getUrl()` instead of `RteHtmlParser->getUrl()`.

Use `GeneralUtility::getIndpEnv('TYPO3_SITE_URL')` instead of `RteHtmlParser->siteUrl()`.

.. index:: PHP-API, RTE
