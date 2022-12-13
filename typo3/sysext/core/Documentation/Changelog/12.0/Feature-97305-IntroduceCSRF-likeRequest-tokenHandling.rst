.. include:: /Includes.rst.txt

.. _feature-97305-1664099950:

============================================================
Feature: #97305 - Introduce CSRF-like request-token handling
============================================================

See :issue:`97305`

Description
===========

A CSRF-like request-token handling has been introduced to mitigate
potential cross-site requests on actions with side-effects. This approach
does not require an existing server-side user session, but uses a nonce
(number used once) as a "pre-session". The main scope is to ensure a user
actually has visited a page, before submitting data to the web server.

This token can only be used for HTTP methods `POST`, `PUT` or `PATCH`, but
for instance not for `GET` request.

New :php:`\TYPO3\CMS\Core\Middleware\RequestTokenMiddleware` resolves
request-tokens and nonce values from a request and enhances responses with
a nonce value in case the underlying application issues one. Both items are
serialized as JSON Web Token (JWT) hash signed with `HS256`. Request-tokens
use the provided nonce value during signing.

Session cookie names involved for providing the nonce value:

* `typo3nonce_[hash]` in case request served with plain HTTP
* `__Secure-typo3nonce_[hash]` in case request served with secured HTTPS

Submitting request-token value to application:

* HTTP body, e.g. in `<form>` via parameter `__RequestToken`
* HTTP header, e.g. in XHR via header `X-TYPO3-Request-Token`

The sequence looks like the following:

1. Retrieve nonce and request-token values
------------------------------------------

This happens on the previous legitimate visit on a page that offers
a corresponding form that shall be protected. The `RequestToken` and `Nonce`
objects (later created implicitly in this example) are organized in the new
:php:`\TYPO3\CMS\Core\Context\SecurityAspect`.

..  code-block:: php

    use \TYPO3\CMS\Core\Context\Context;
    use \TYPO3\CMS\Core\Security\RequestToken;
    use \TYPO3\CMS\Fluid\View\StandaloneView;

    class MyController
    {
        protected StandaloneView $view;
        protected Context $context;

        public function showFormAction()
        {
            // creating new request-token with scope
            // 'my/process' and hand over to view
            $requestToken = RequestToken::create('my/process');
            $this->view->assign('requestToken', $requestToken)
                // ...
            }

        public function processAction()
        {
        }
    }

..  code-block:: html

    <!-- in ShowForm.html template: assign request-token object for view-helper -->
    <f:form action="process" requestToken="{requestToken}>...</f:form>

The HTTP response on calling the shown controller-action above will be like this:

..  code-block:: text

    HTTP/1.1 200 OK
    Content-Type: text/html; charset=utf-8
    Set-Cookie: typo3nonce_[hash]=[nonce-as-jwt]; path=/; httponly; samesite=strict

    ...
    <form action="/my/process" method="post">
        ...
        <input type="hidden" name="__request_token" value="[request-token-as-jwt]">
        ...
    </form>

2. Invoke action request and provide nonce and request-token values
-------------------------------------------------------------------

When submitting the form and invoking the corresponding action, same-site
cookies `typo3nonce_[hash]` and request-token value `__RequestToken` are sent
back to the server. Without using a separate nonce in a scope that is protected
by the client, corresponding request-token could be easily extracted from markup
and used without having the possibility to verify the procedural integrity.

Middleware :php:`\TYPO3\CMS\Core\Middleware\RequestTokenMiddleware` takes care
of providing received nonce and received request-token values in
:php:`\TYPO3\CMS\Core\Context\SecurityAspect`. The handling controller-action
needs to verify that the request-token has the expected `'my/process'` scope.

..  code-block:: php

    class MyController
    {
        protected \TYPO3\CMS\Fluid\View\StandaloneView $view;
        protected \TYPO3\CMS\Core\Context\Context $context;

        public function showFormAction() {}

        public function processAction()
        {
            $securityAspect = \TYPO3\CMS\Core\Context\SecurityAspect::provideIn($this->context);
            $requestToken = $securityAspect->getReceivedRequestToken();

            if ($requestToken === null) {
                // no request-token was provided in request
                // e.g. (overridden) templates need to be adjusted
            } elseif ($requestToken === false) {
                // there was a request-token, which could not be verified with the nonce
                // e.g. when nonce cookie has been overridden by another HTTP request
            } elseif ($requestToken->scope !== 'my/process') {
                // there was a request-token, but for a different scope
                // e.g. when a form with different scope was submitted
            } else {
                // request-token was valid and for the expected scope
                $this->doTheMagic();
                // middleware takes care to remove the the cookie in case no other
                // nonce value shall be emitted during the current HTTP request
                $requestToken->getSigningSecretIdentifier() !== null) {
                    $securityAspect->getSigningSecretResolver()->revokeIdentifier(
                        $requestToken->getSigningSecretIdentifier()
                    );
                }
            }
        }
    }

Intercept & Adjust Request Token
--------------------------------

Scenarios that are not using a login callback without having the possibility to
submit a request-token, :php:`\TYPO3\CMS\Core\Authentication\Event\BeforeRequestTokenProcessedEvent`
can be used to generate the token individually.

..  code-block:: php

    use TYPO3\CMS\Core\Authentication\Event\BeforeRequestTokenProcessedEvent;
    use TYPO3\CMS\Core\Security\RequestToken;

    final class ProcessRequestTokenListener
    {
        public function __invoke(BeforeRequestTokenProcessedEvent $event): void
        {
            $user = $event->getUser();
            $requestToken = $event->getRequestToken();
            // fine, there is a valid request-token
            if ($requestToken instanceof RequestToken) {
                return;
            }
            // validate individual requirements/checks
            // ...
            $event->setRequestToken(
                RequestToken::create('core/user-auth/' . $user->loginType);
            );
        }
    }

Impact
======

In case a form is protected with the new request-token, actors have to visit the
page containing the form before being able to actually submit data to the
underlying server-side processing.

When working with multiple browser tabs, an existing nonce value (stored as
session cookie in users' browser) might be overridden.

The current concept uses a :php:`\TYPO3\CMS\Core\Security\NoncePool` which
supports five different nonces in the same request. The pool purges nonces
15 minutes (900 seconds) after they have been issued.

.. index:: Backend, Fluid, Frontend, PHP-API, ext:core
