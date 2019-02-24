.. include:: ../../Includes.txt

====================================================================
Deprecation: #85892 - Various methods regarding sys_domain-resolving
====================================================================

See :issue:`85892`

Description
===========

Various methods specific for handling `sys_domain` records have been marked as deprecated. As the new site handling is in place in favor of using `sys_domain`
records, these methods have been centralized in a :php:`LegacyDomainResolver` class, which is however marked as internal.

Instead, generating URLs should be done via the new PageUriBuilder and Routing API, which covers both the new
site handling and the specific sys_domain record.

The following methods have been marked as deprecated:

* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->domainNameMatchesCurrentRequest()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getDomainDataForPid()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::getDomainStartPage()`
* :php:`TYPO3\CMS\Backend\Utility\BackendUtility::firstDomainRecord()`


Impact
======

Calling any of the methods will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any installation with custom functionality regarding `sys_domain` handling where any of the methods mentioned above are used.


Migration
=========

Migrate to either the new Routing API (finalized for 9 LTS) or implement the functionality in your own, or use the :php:`LegacyDomainResolver` class,
but since the concept of sys_domain handling will be removed in TYPO3 v10, consider use of the Site handling functionality instead.

.. index:: Frontend, Backend, FullyScanned
