.. include:: ../../Includes.txt

==========================================================
Breaking: #81460 - Deprecate getByTag() on cache frontends
==========================================================

See :issue:`81460`

Description
===========

The following public method and property have been removed without any substitute
since it invoked or was used in combination with Cache\FrontendInterface::getByTag
method which has been deprecated and removed from the interface declaration.

* TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::extGetNumberOfCachedPages
* TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::$extPageInTreeInfo


Impact
======

Calling TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::extGetNumberOfCachedPages will
result in a PHP fatal error. Using the property FrontendUserAuthentication::$extPageInTreeInfo will
return an implicit `null` instead of an `array`.


Affected Installations
======================

All that make use of TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::extGetNumberOfCachedPages
or property TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::$extPageInTreeInfo.


Migration
=========

Remove invocation of TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::extGetNumberOfCachedPages
and property TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::$extPageInTreeInfo.

.. index:: PHP-API, FullyScanned
