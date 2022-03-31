.. include:: /Includes.rst.txt

=====================================================================
Breaking: #93073 - AbstractUserAuthentication->forceSetCookie removed
=====================================================================

See :issue:`93073`

Description
===========

The public property :php:`forceSetCookie`
is removed from the PHP class :php:`AbstractUserAuthentication`.

This property served to ensure that a cookie should be added
at any times, which is useful for time-based cookies, which only
happen in Frontend user sessions. This property is now moved as a protected
property into the :php:`FrontendUserAuthentication` class and used in this class
solely to reduce the complexity of the internal logic as well as outside API.


Impact
======

Setting this property has no effect anymore, setting this property on a Frontend User object will trigger a PHP warning.


Affected Installations
======================

TYPO3 installations with third-party extensions and special cookie handling, which is very unlikely.


Migration
=========

If custom functionality for setting cookies is needed, it is highly
recommended to send cookies manually via a PSR-15 middleware.

.. index:: Backend, Frontend, PHP-API, FullyScanned, ext:core
