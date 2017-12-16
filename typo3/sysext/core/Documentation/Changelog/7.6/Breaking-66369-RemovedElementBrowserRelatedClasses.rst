
.. include:: ../../Includes.txt

=========================================================
Breaking: #66369 - Removed ElementBrowser related classes
=========================================================

See :issue:`66369`

Description
===========

The following classes have been removed without replacement

	* TYPO3\CMS\Core\ElementBrowser\ElementBrowserHookInterface
	* TYPO3\CMS\Recordlist\Browser\ElementBrowser
	* TYPO3\CMS\Rtehtmlarea\BrowseLinks
	* TYPO3\CMS\Rtehtmlarea\FolderTree
	* TYPO3\CMS\Rtehtmlarea\PageTree


Impact
======

Any code still using the aforementioned classes will cause a fatal error.


Affected Installations
======================

Any code still using the aforementioned classes.


Migration
=========

Use the new API for adding element browsers or link handlers.
