.. include:: ../../Includes.txt

=================================================================
Breaking: #88540 - Changed Request Workflow for Frontend Requests
=================================================================

See :issue:`88540`

Description
===========

The "Frontend Request Workflow" is the PHP code responsible for
setting up various functionality. This includes Login/Permission Check, resolving
the current site + language, and checking the page + rootline, then
parsing TypoScript, which will then lead to building content (or taken from
cache), until the actual output.

Since TYPO3 v9, this is all built via PSR-15 middlewares, the PSR-15 Request Handler,
and the global TypoScriptFrontendController (TSFE).

For TYPO3 v10.0, various changes were made in order to separate concerns / logic
from each other, allowing to easily exchange certain components with
other / extended functionality.

The following changes have been made:

- Storing session data from a Frontend User Session / Anonymous session is now
triggered within the Frontend User ("frontend-user-authenticator") Middleware,
at a later point. Before this was part of the RequestHandler logic after content
was put together. This was due to legacy reasons of the previous hook execution order.


Impact
======

Hooks that depend on certain functionality being made before or after a hook is
called will likely have a different behavior when:

- A Frontend Session is used within Hooks.

Anything related to regular plugins / content / TypoScript is not affected.


Affected Installations
======================

Any hooks from third party extensions that run

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']`

and depend on the frontend session data being written.


Migration
=========

Consider using a PSR-15 middleware instead of using a hook, or explicitly
call "storeSessionData" within the PHP hook if necessary.

.. index:: Frontend, PHP-API, NotScanned
