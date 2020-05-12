.. include:: ../../Includes.txt

==========================================================
Important: #91242 - Introduce Backend Route Referrer Check
==========================================================

See :issue:`91242`

Description
===========

Public backend routes (those having option :php:`'access' => 'public'` in
:file:`Configuration/Backend/Routes.php`) do not require any session token,
but can be used to internally redirect to a route that requires a session
token. For this context it is required that a backend user is currently
logged in having a valid session.

This scenario can lead to situations that an existing cross-site scripting
vulnerability (XSS) allows to bypass mentioned session token - which can be
considered as cross-site request forgery (CSRF).
The difference in terminology is that this scenario occurs on same-site
requests and not cross-site - however, potential security implications are
still the same.

In order to mitigate described potential backend routes can enforce the
existence of a HTTP `Referer` header by adding new option `referrer` to
routes in :file:`Configuration/Backend/Routes.php`.

.. code-block:: php

    'main' => [
        'path' => '/main',
        'referrer' => 'required,refresh-empty',
        'target' => Controller\BackendController::class . '::mainAction'
    ],

Values for option `referrer` are declared as comma-separated list:

* `required` enforces existence of HTTP `Referer` header that has to match the
  currently used backend URL (e.g. `https://example.org/typo3/`), the request
  will be denied otherwise.
* `refresh-empty` triggers a HTML based refresh in case HTTP `Referer` header
  is not given or empty - this attempt uses an HTML refresh, since regular HTTP
  `Location` redirect still would not set a referrer. It implies this technique
  should only be used on plain HTML responses and won't have any impact e.g. on
  JSON or XML response types.

This technique should be used on all public routes (without session token) that
internally redirect to a restricted route (having a session token). The goal is
to protect and keep information about the current session token internal.

The request sequence in the TYPO3 core looks like this:

* HTTP request to `https://example.org/typo3/` having a valid user session
* internally **public** backend route `/login` is processed
* internally redirects to **restricted** backend route `/main` since an
  existing and valid backend user session was found
  + HTTP redirect to `https://example.org/typo3/index.php?route=/main&token=...`
  + exposing the token is mitigated with `referrer` route option mentioned above

Please keep in mind these steps are part of a mitigation strategy, which requires
to be aware of mentioned implications when implementing custom web applications.

.. index:: Backend, PHP-API, ext:backend
