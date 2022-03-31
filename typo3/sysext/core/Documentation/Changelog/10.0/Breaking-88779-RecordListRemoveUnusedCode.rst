.. include:: /Includes.rst.txt

=================================================
Breaking: #88779 - RecordList: Remove unused code
=================================================

See :issue:`88779`

Description
===========

The following public properties have been removed from :php:`TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList`:

* :php:`modSharedTSconfig`
* :php:`no_noWrap`
* :php:`setLMargin`
* :php:`JScode`
* :php:`leftMargin`

The following public methods have been removed from :php:`TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList`:

* :php:`getButtons`
* :php:`thumbCode`
* :php:`requestUri`
* :php:`writeTop`
* :php:`fwd_rwd_nav`
* :php:`fwd_rwd_HTML`


Impact
======

Calling one of the mentioned methods will trigger a fatal :php:`E_ERROR`.


Migration
=========

Use :php:`BackendUtility::thumbCode` instead of :php:`thumbCode`. Use :php:`listURL` instead of :php:`requestUri`.

.. index:: PHP-API, FullyScanned, ext:recordlist
