.. include:: /Includes.rst.txt

==========================================================
Breaking: #81460 - Deprecate getByTag() on cache frontends
==========================================================

See :issue:`81460`

Description
===========

The following public method and property have been removed without any substitute
since it invoked or was used in combination with Cache\FrontendInterface::getByTag
method which has been deprecated and removed from the interface declaration.

* :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::extGetNumberOfCachedPages`
* :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::$extPageInTreeInfo`


Impact
======

Calling :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::extGetNumberOfCachedPages` will
result in a PHP fatal error. Using the property :php:`FrontendUserAuthentication::$extPageInTreeInfo` will
return an implicit `null` instead of an `array`.


Affected Installations
======================

All that make use of :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::extGetNumberOfCachedPages`
or property :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::$extPageInTreeInfo`.


Migration
=========

Remove invocation of :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::extGetNumberOfCachedPages`
and property :php:`TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::$extPageInTreeInfo`.

.. index:: PHP-API, FullyScanned
