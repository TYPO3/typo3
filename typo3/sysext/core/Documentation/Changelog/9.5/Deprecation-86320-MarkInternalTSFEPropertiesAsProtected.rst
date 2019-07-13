.. include:: ../../Includes.txt

=================================================================
Deprecation: #86320 - Mark internal $TSFE properties as protected
=================================================================

See :issue:`86320`

Description
===========

The following properties of class :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` have changed their visibility to be protected from public and should not be called any longer.
The properties are only used and needed internally.

* :php:`loginAllowedInBranch_mode`
* :php:`cacheTimeOutDefault`
* :php:`cacheContentFlag`
* :php:`cacheExpires`
* :php:`isClientCachable`
* :php:`no_cacheBeforePageGen`
* :php:`tempContent`
* :php:`pagesTSconfig`
* :php:`uniqueCounter`
* :php:`uniqueString`
* :php:`lang`


Impact
======

Calling any of the properties will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation directly accessing any of the mentioned properties.


Migration
=========

Properties are only for internal use, no migration available.

.. index:: Frontend, FullyScanned, ext:frontend
