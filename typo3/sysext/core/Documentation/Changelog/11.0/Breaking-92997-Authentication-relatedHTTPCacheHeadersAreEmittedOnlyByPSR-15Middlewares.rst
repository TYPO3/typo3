.. include:: /Includes.rst.txt

===================================================================================================
Breaking: #92997 - Authentication-related HTTP cache headers are emitted only by PSR-15 middlewares
===================================================================================================

See :issue:`92997`

Description
===========

In previous TYPO3 versions, when a user session was initiated or set
(e.g. due to login or cookie), class :php:`AbstractUserAuthentication` was instructed
to send HTTP headers immediately via the PHP function :php:`header()`.

These headers were sent directly to the client without having a chance to
manipulate a response, or simulate this behavior via proper tests in a testing
suite.

These HTTP headers for not caching a HTTP response were already attached to the
PSR-7 Response when an active Backend user was available in Frontend and Backend
requests, but not when a Frontend user was logged in.

The internal methods in class :php:`AbstractUserAuthentication` are removed.

Impact
======

These headers are now only sent via the PSR-7 Response object, and emitted at
the very end of a Request/Response lifecycle in a TYPO3 Application (for Frontend
and Backend Requests), and not via the :php:`header()` function anymore.


Affected Installations
======================

TYPO3 installations with custom extensions manipulating HTTP headers or the
options within class :php:`AbstractUserAuthentication` to send such headers.


Migration
=========

If any changes regarding the PSR-7 Response headers are needed, it is
recommended to build a custom PSR-15 middleware in a TYPO3 Extension.

.. index:: Backend, Frontend, PHP-API, FullyScanned, ext:core
