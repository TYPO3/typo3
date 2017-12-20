.. include:: ../../Includes.txt

====================================================================
Breaking: #82991 - Record list "Localization View" is always enabled
====================================================================

See :issue:`82991`

Description
===========

The option / checkbox "Localization View" in TYPO3's List module was removed, as the functionality is now
always enabled.


Impact
======

The PageTSconfig option :ts:`mod.web_list.enableLocalizationView` has no effect anymore.


Affected Installations
======================

Any multi-language installation using the TSconfig option above to e.g. disable the localization view.


Migration
=========

Remove the TSconfig option.

.. index:: TSConfig, Backend, NotScanned