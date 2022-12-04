.. include:: /Includes.rst.txt

.. _deprecation-99201-1669561044:

===========================================================================
Deprecation: #99201 - UserSessionManager->createFromGlobalCookieOrAnonymous
===========================================================================

See :issue:`99201`

Description
===========

The PHP method :php:`\TYPO3\CMS\Core\Session\UserSessionManager->createFromGlobalCookieOrAnonymous`
has been deprecated. It served as a low-level API to create a user session based
on the superglobal :php:`$_COOKIE` variable. However, the usage of PHP
superglobals should be avoided in TYPO3 code. As TYPO3 Core is moving towards
accessing request information via PSR-7 request attribute, this method is also
deprecated, even though it was only introduced in TYPO3 v11.0.


Impact
======

Calling the method directly within PHP code of a third-party extension will
trigger a PHP deprecation message.


Affected installations
======================

The method was only introduced in TYPO3 v11.0, and only acted as a
backwards-compatibility layer for using the UserSessionManager API class in
legacy code, which is why it is very unlikely that this method is called directly
in any TYPO3 extension. However, the Extension Scanner will pick up any
usages of this method.

TYPO3 extensions usually do not use the :php:`UserSessionManager` directly to create
a user session.


Migration
=========

The :php:`UserSessionManager` API also provides the
:php:`createFromRequestOrAnonymous(ServerRequestInterface $request)` method when
the API itself was added. The method achieves the same logic based on a PSR-7
request. Use this method instead and use PSR-7 as much as possible.

.. index:: PHP-API, FullyScanned, ext:core
