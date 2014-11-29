===================================================
Breaking: #53568 - option alternateBgColors removed
===================================================

Description
===========

The PageTSConfig option :ts:`mod.web_list.alternateBgColors` is removed without substitution.


Impact
======

Extensions that extend the DatabaseRecordList and are using the property :ts:`alternateBgColors`

The option in the TableListViewHelper has been deprecated and will be removed in TYPO3 CMS 8.

Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed property.


Migration
=========

Remove the call to the removed property.