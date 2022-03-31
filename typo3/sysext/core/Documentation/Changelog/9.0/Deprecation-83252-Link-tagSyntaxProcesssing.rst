.. include:: /Includes.rst.txt

=================================================
Deprecation: #83252 - link-tag syntax processsing
=================================================

See :issue:`83252`

Description
===========

The old-fashioned way of storing links as <link>-tags in the database was migrated in TYPO3 v8, however, the code
is still in place.

Using the following hooks is not encouraged:

- :php:`$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksRte_PostProc']`
- :php:`$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['modifyParams_LinksDb_PostProc']`

The following public methods in RteHtmlParser have been marked as deprecated:

- :php:`TYPO3\CMS\Core\Html\RteHtmlParser->TS_links_rte()`
- :php:`TYPO3\CMS\Core\Html\RteHtmlParser->transformStyledATags()`

The second argument of the PHP method :php:`TYPO3\CMS\Core\Html\RteHtmlParser->TS_AtagToAbs()` has been marked
as deprecated.


Impact
======

Transforming any content with a :html:`<link>` tag will trigger deprecation warning. Additionally, calling one of the
deprecated methods or triggering any of the hooks within RteHtmlParser will trigger a deprecation warning.

Using the method :php:`TS_AtagToAbs()` with a second argument set will trigger a deprecation warning.


Affected Installations
======================

Any installation which makes use of the legacy <link> syntax or hasn't fully migrated to TYPO3 v8 yet.


Migration
=========

Migrate all content to proper anchor-tags, so the hooks are not necessary anymore.

.. index:: PHP-API, RTE, FullyScanned
