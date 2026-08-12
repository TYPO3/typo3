..  include:: /Includes.rst.txt

..  _feature-111399-1786492801:

================================================
Feature: #111399 - Native backend authenticators
================================================

See :issue:`111399`

Description
===========

Backend session resolution and password evaluation are now performed by
native authenticators of the authentication pipeline, no longer depending
on the legacy :php:`T3_SERVICES` authentication service chain:

*   :php:`\TYPO3\CMS\Backend\Security\PasswordAuthenticator` processes
    submitted credentials on the backend login routes, including the
    request token gate, rate limiting, password rehashing, session fixation
    protection and the established logging and PSR-14 events.
*   :php:`\TYPO3\CMS\Backend\Security\SessionAuthenticator` resolves an
    existing user session, handles logout requests and expired sessions,
    and initiates the multi-factor authentication challenge when required.

The native authenticators only engage when the core authentication service
is the only available authentication service. As soon as an extension adds
an available authentication service to the chain, the core service is
disabled or its class is replaced (via the service configuration or an
XCLASS), a :php:`postUserLookUp` hook is registered, or the chain behavior
is modified via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']`, the wrapped
legacy service chain processes the request exactly as before, so existing
single sign-on or LDAP extensions keep working unchanged. Services that are
not available are ignored, as the legacy chain never runs them either.

Impact
======

Installations without third-party authentication services use the native
authenticators automatically. Five deliberate behavior refinements apply in
this mode:

*   Login credentials sent to arbitrary backend URLs outside the login
    routes are ignored. Password processing is bound to the login
    endpoints.
*   A login is only processed for POST requests with the login status in the
    request body. A login status in the query string is ignored.
*   A logout via the ``login_status`` parameter is only processed for
    POST requests with the parameter in the request body. A link carrying
    ``login_status=logout`` in the query string no longer logs out the
    user. The logout route of the backend is not affected.
*   A pending multi-factor authentication requirement is expressed as a
    redirect to the MFA route (or a 401 response for AJAX calls) instead of
    an exception thrown through the middleware stack. The behavior for
    users is identical.
*   A login with a username matching more than one backend user record is
    refused instead of using the first match.

An XCLASS of :php:`\TYPO3\CMS\Core\Authentication\BackendUserAuthentication`
overriding the authentication methods (for example :php:`start()` or
:php:`checkAuthentication()`) is not called on the native path.

..  index:: PHP-API, ext:backend
