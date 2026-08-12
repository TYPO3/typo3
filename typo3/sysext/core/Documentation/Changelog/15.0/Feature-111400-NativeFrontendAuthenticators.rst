..  include:: /Includes.rst.txt

..  _feature-111400-1786492802:

=================================================
Feature: #111400 - Native frontend authenticators
=================================================

See :issue:`111400`

Description
===========

Frontend session resolution and password evaluation are now performed by
native authenticators of the authentication pipeline, no longer depending
on the legacy :php:`T3_SERVICES` authentication service chain:

*   :php:`\TYPO3\CMS\Frontend\Security\FrontendPasswordAuthenticator`
    processes credentials submitted with :php:`logintype=login` in the body
    of a POST request to any page URL. Login forms placed on arbitrary
    pages — such as the felogin plugin — keep working as before: The
    trigger is now an explicit condition (the login parameter together with
    the signed request token) instead of credential detection on every
    request. A login type in the query string is ignored. The storage page
    restriction provided by the felogin form, permanent login handling
    ("stay logged in") and the established PSR-14 events are preserved.
*   :php:`\TYPO3\CMS\Frontend\Security\FrontendSessionAuthenticator`
    resolves an existing user session and handles logout requests, always
    resolving group data so that group simulation keeps working.

The realm the request is authenticated against is derived from the resolved
site, laying the ground for site-specific frontend authentication
configuration such as frontend multi-factor authentication.

Outlook: Multi-factor authentication in the frontend
----------------------------------------------------

The authentication pipeline provides the building blocks to add
multi-factor authentication for frontend users, as the backend does:

*   Authenticators are registered for the realm "frontend" with
    :php:`#[AsAuthenticator(realm: 'frontend', priority: …)]` and are asked
    in the order of their priority. The first result other than
    :php:`NotApplicable` ends the pipeline.
*   An authenticator may answer with a :php:`Challenge` instead of
    authenticating the user. The frontend returns the response of the
    challenge as it is, for example a redirect to a page asking for the
    second factor. The backend uses the same mechanism for its
    multi-factor authentication.
*   The MFA providers, such as TOTP and recovery codes, and the
    :php:`MfaProviderRegistry` work with any user authentication, so with
    frontend users as well. The table :sql:`fe_users` already provides the
    field :sql:`mfa` for the provider settings.

An authenticator registered with a priority between the
:php:`FrontendPasswordAuthenticator` (100) and the
:php:`FrontendSessionAuthenticator` (90) can, for example, answer every
request of a session whose user has an active provider but has not passed
the second factor yet with a challenge. Once the code is verified, the
session is marked as having passed it. As the realm is derived from the
site, such a requirement can be enabled per site.

A frontend form to verify the second factor and to set up providers is not
part of this change. The pipeline API is still marked as internal and may
change until it is declared public.

Analogous to the backend, the native authenticators only engage when the
core authentication service is the only available authentication service.
As soon as an extension adds an available authentication service, the core
service is disabled or its class is replaced, a :php:`postUserLookUp` hook
is registered, or the chain behavior is modified via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']`, the wrapped
legacy service chain handles the request unchanged.

Impact
======

Installations without third-party authentication services use the native
authenticators automatically. The established frontend login behavior,
including login forms on any page and logout links carrying
``logintype=logout`` in the query string, is preserved, with two
refinements:

*   A login is only processed for POST requests with the login type in the
    request body. A login type in the query string is ignored.
*   A login with a username matching more than one frontend user record in
    the allowed storage pages is refused instead of using the first match.

An XCLASS of :php:`\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication`
overriding the authentication methods (for example :php:`start()` or
:php:`checkAuthentication()`) is not called on the native path.

..  index:: PHP-API, ext:frontend
