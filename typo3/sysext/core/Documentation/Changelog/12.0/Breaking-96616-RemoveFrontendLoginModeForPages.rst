.. include:: /Includes.rst.txt

.. _breaking-96616:

=======================================================
Breaking: #96616 - Remove Frontend Login Mode for pages
=======================================================

See :issue:`96616`

Description
===========

In order to reduce complexity for frontend requests,
the rarely used `frontend user login mode` functionality has been removed.

It previously allowed to define branches, which should behave as if a user
or usergroup was not logged in, even though a user was kept logged in as the cookie was
not removed during such a request. This feature was only introduced back in 2004 by Kasper
to overcome caching issues on typo3.org and is considered an edge-case feature,
which is better suited in an extension solved via a PSR-15 middleware nowadays.

As a consequence, next to the corresponding DB / TCA field :php:`pages.fe_login_mode`
the following public methods have been removed:

- :php:`\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication->hideActiveLogin()`
- :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->checkIfLoginAllowedInBranch()`

Additionally, the following TypoScript configuration has no effect anymore:

- :typoscript:`config.sendCacheHeaders_onlyWhenLoginDeniedInBranch`

Impact
======

The functionality is no longer part of TYPO3 Core. Calling the related methods
in custom extension code will lead to a fatal PHP error. The extension scanner
will detect usages as weak match.

Affected Installations
======================

TYPO3 installations currently using the functionality or calling the
mentioned methods in custom extension code, which is very unlikely.

This can be checked by searching for database records in the DB "pages"
table having "fe_login_mode > 0".

Migration
=========

Remove any usage of the mentioned methods.

In case you currently rely on the functionality, use the upgrade wizard
provided by the install tool to fetch and load the public `fe_login_mode`
extension from `TER <https://extensions.typo3.org/extension/fe_login_mode>`_.
This extension provides the same functionality using a PSR-15 middleware.

.. index:: Frontend, TCA, FullyScanned, ext:frontend
