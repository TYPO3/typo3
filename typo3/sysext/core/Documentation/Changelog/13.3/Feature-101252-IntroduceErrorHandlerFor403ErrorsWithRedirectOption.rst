.. include:: /Includes.rst.txt

.. _feature-101252-1715447531:

=============================================================================
Feature: #101252 - Introduce ErrorHandler for 403 errors with redirect option
=============================================================================

See :issue:`101252`

Description
===========

The new error handler :php:`RedirectLoginErrorHandler` has been added,
which makes it possible to redirect the user to a configurable page.

Requesting a login-protected URL would usually return a generic HTTP 403 error
in case of a missing fulfilled access permissions and the configuration
:php:`typolinkLinkAccessRestrictedPages = NONE` (default)
is set.

By enabling this new handler via the site settings, the 403 response
can be handled and a custom redirect can be performed.

The :php:`RedirectLoginErrorHandler` allows to define a
:php:`loginRedirectTarget`, which must be configured to the page, where the
login process is handled. Additionally, the :php:`loginRedirectParameter`
must be set to the URL parameter that will be used to hand over the original
URL to the target page.

The redirect ensures that the original URL is added to the configured GET
parameter :php:`loginRedirectParameter`, so that the user can be redirected
back to the original page after a successful login.

The error handler allows :php:`return_url` or :php:`redirect_url` as values
for :php:`loginRedirectParameter`. Those values are used in extensions like
`EXT:felogin` or `EXT:oidc`.

..  important::

    Redirection to the originating URL via URI arguments requires that
    extensions like `EXT:felogin` are configured to allow these redirect modes
    (for example via
    :typoscript:`plugin.tx_felogin_login.settings.redirectMode=getpost,loginError`)

The new error handler works (with some minor exceptions) similar to the
"Forbidden (HTTP Status 403)" handler in TYPO3 extension `EXT:sierrha`.
It will still emit generic 403 HTTP error messages in certain scenarios,
like when a user is already logged in, but the permissions are not
satisfied.

Impact
======

It is now possible to configure a login redirection process when a user has no
access to a page and a 403 error is thrown, so that after login the
originating URL is requested again. Previously, this required custom
Middlewares or implementations of :php:`PageErrorHandlerInterface`.

.. index:: Frontend, ext:core
