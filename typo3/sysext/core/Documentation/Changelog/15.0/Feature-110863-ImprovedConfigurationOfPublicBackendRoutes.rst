..  include:: /Includes.rst.txt

..  _feature-110863-1790781220:

==================================================================
Feature: #110863 - Improved configuration of public backend routes
==================================================================

See :issue:`110863`

Description
===========

Whether a backend route can be reached without a backend user has been
defined in two independent places: a hardcoded list of paths in
:php:`\TYPO3\CMS\Backend\Middleware\BackendUserAuthenticator` and the route
option :php:`access`, which only controlled the request token. Both
definitions had diverged, for example for the route
:php:`ajax_login_refresh`.

The route option :php:`access` is now the single source of truth. It is backed
by the new enum :php:`\TYPO3\CMS\Backend\Routing\RouteAccess` and accepts
the values:

*   :php:`anonymous`: The route can be reached without a backend user and
    without a request token, for example the login or the password reset.
*   :php:`authenticated` (default): The route requires a backend user and a
    valid request token.
*   :php:`authenticated-without-token`: The route requires a backend user, but
    no request token.

Extensions can use :php:`anonymous` for routes that must be reachable
without a session, for example callback endpoints of single sign-on
implementations:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Routes.php

    return [
        'my_sso_callback' => [
            'path' => '/my-extension/sso/callback',
            'access' => 'anonymous',
            'target' => SsoCallbackController::class . '::handleRequest',
        ],
    ];

Any other value of :php:`access` is treated as :php:`authenticated`, so a
misspelled value can never expose a route. The class
:php:`\TYPO3\CMS\Backend\Routing\Route` provides the new method
:php:`getAccess()`, which returns the :php:`RouteAccess` with the methods
:php:`requiresAuthentication()` and :php:`requiresRequestToken()`.

Additionally, the new backend middleware
:php:`\TYPO3\CMS\Backend\Middleware\FetchMetadataGuard` rejects
state-changing requests (any method except :php:`GET`, :php:`HEAD` and
:php:`OPTIONS`) to non-anonymous routes with a :http:`403` response, if the
browser reports them as :html:`cross-site` or :html:`same-site` via the
:html:`Sec-Fetch-Site` header. If the header is missing, the :html:`Origin`
header is compared with the host of the request. The middleware can be
disabled with the feature toggle
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceFetchMetadata']`.

The CLI command :bash:`typo3 debug:backend:routes` now lists the access and
the request token requirement of each route and can be filtered with the
new option :bash:`--access`, for example :bash:`--access=anonymous`.

Impact
======

Extensions can register backend routes that are processed without a backend
user by using :php:`'access' => 'anonymous'`. The routes
:php:`ajax_login_refresh`, :php:`state-tracker` and :php:`language_domain`
are now declared consistently.

The route access :php:`public` has been deprecated, see
:ref:`deprecation-110863-1790766913`.

Backend requests, which change data and originate from another site, are now
denied by default.

..  index:: Backend, PHP-API, ext:backend
