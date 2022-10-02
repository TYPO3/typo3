.. include:: /Includes.rst.txt

.. _breaking-97305-1664100009:

==================================================
Breaking: #97305 - Introduce CSRF-like login token
==================================================

See :issue:`97305`

Description
===========

:php:`\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication` requires a
CSRF-like request-token to continue with the authentication process and to
create an actual server-side user session.

The request-token has to be submitted by one of these ways:

* HTTP body, e.g. in `<form>` via parameter `__request_token`
* HTTP header, e.g. in XHR via header `X-TYPO3-Request-Token`

Impact
======

Core user authentication is protected by a CSRF-like request-token, to
mitigate `Login CSRF <https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html>`__.

Custom implementations for login templates or client-side authentication
handling have to be adjusted to submit the required request-token.

Affected installations
======================

Sites having custom implementations for login templates or client-side authentication.

Migration
=========

The :php:`\TYPO3\CMS\Core\Security\RequestToken` signed with a :php:`\TYPO3\CMS\Core\Security\Nonce`
needs to be sent as JSON Web Token (JWT) to the server-side application handling of
the Core user authentication process. The scope needs to be :php:`core/user-auth/be`
or :php:`core/user-auth/fe` - depending on whether authentication is applied in
the website's backend or frontend context.

Example for overridden backend login HTML template (`ext:backend`)
------------------------------------------------------------------

..  code-block:: diff

    --- a/typo3/sysext/backend/Resources/Private/Layouts/Login.html
    +++ b/typo3/sysext/backend/Resources/Private/Layouts/Login.html
     <input type="hidden" name="redirect_url" value="{redirectUrl}" />
     <input type="hidden" name="loginRefresh" value="{loginRefresh}" />
    +<input type="hidden" name="{requestTokenName}" value="{requestTokenValue}" />

Example for overridden frontend login HTML template (`ext:felogin`)
-------------------------------------------------------------------

..  code-block:: diff

    --- a/typo3/sysext/felogin/Resources/Private/Templates/Login/Login.html
    +++ b/typo3/sysext/felogin/Resources/Private/Templates/Login/Login.html
    -<f:form target="_top" fieldNamePrefix="" action="login">
    +<f:form target="_top" fieldNamePrefix="" action="login" requestToken="{requestToken}">

More details are explained in corresponding documentation on
:ref:`Feature #87616: Introduce CSRF-like request-token handling <feature-97305-1664099950>`.

.. index:: Backend, Fluid, Frontend, NotScanned, ext:core
