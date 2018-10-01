.. include:: ../../Includes.txt

==========================================================================
Deprecation: #86440 - Internal Methods and properties within RteHtmlParser
==========================================================================

See :issue:`86440`

Description
===========

Several methods and properties in class :php:`TYPO3\CMS\Core\Html\RteHtmlParser` have been changed
from public to protected visibility.
Some additional functionality has been marked as deprecated, as this has been replaced with the new RTE configuration
since TYPO3 v8.

The following properties have changed visibility to protected:

* :php:`blockElementList`
* :php:`recPid`
* :php:`elRef`
* :php:`tsConfig`
* :php:`procOptions`
* :php:`TS_transform_db_safecounter`
* :php:`getKeepTags_cache`
* :php:`allowedClasses`

The following public methods have changed visibility to protected:

* :php:`TS_images_db()`
* :php:`TS_links_db()`
* :php:`TS_transform_db()`
* :php:`TS_transform_rte()`
* :php:`HTMLcleaner_db()`
* :php:`getKeepTags()`
* :php:`divideIntoLines()`
* :php:`setDivTags()`
* :php:`getWHFromAttribs()`
* :php:`urlInfoForLinkTags()` (deprecated, not in use anymore)
* :php:`TS_AtagToAbs()`

The following processing options (`RTE.proc.`) have been marked as deprecated:

* :ts:`keepPDIVattribs`
* :ts:`dontRemoveUnknownTags_db`


Impact
======

Setting any of the options, calling the methods above or accessing the properties will trigger a
PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with extensions or custom usages for RTE handling (e.g. `l10nmgr`).


Migration
=========

Migrate to use the public API only and use other options (such as :ts:`allowAttributes` instead of
:ts:`dontRemoveUnknownTags_db`) in order to only run certain instructions on the RteHtmlParser object.

.. index:: RTE, FullyScanned, PHP-API
