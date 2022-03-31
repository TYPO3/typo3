.. include:: /Includes.rst.txt

====================================================================================
Breaking: #93047 - Removed property sendNoCacheHeaders in AbstractUserAuthentication
====================================================================================

See :issue:`93047`

Description
===========

The public property :php:`sendNoCacheHeaders` of class :php:`AbstractUserAuthentication` which was
enabled by default, but disabled in Frontend User objects, ensured that appropriate
HTTP headers telling the client that this HTTP request is not allowed to be
cached by the client.

This property is removed, as this is now built into PSR-15 middlewares for
both Frontend and Backend users since TYPO3 v10.


Impact
======

Setting the property :php:`sendNoCacheHeaders` has no effect anymore.


Affected Installations
======================

TYPO3 installations with custom extensions dealing with session
handling, using this property, which is very unlikely.


Migration
=========

Use a PSR-15 middleware to set headers depending on your needs,
if TYPO3s default header evaluation does not fit your requirements
in Frontend Requests.

.. index:: Backend, Frontend, PHP-API, FullyScanned, ext:frontend
