.. include:: /Includes.rst.txt

.. _breaking-94243-1664786038:

===============================================================
Breaking: #94243 - Send user session cookies as hash-signed JWT
===============================================================

See :issue:`94243`

Description
===========

`JSON Web Tokens (JWT) <https://jwt.io/>`__ are used to transport user session
identifiers in `be_typo_user` and `fe_typo_user` cookies. Using JWT's `HS256`
(HMAC signed based on SHA256) allows to determine whether a session cookie is
valid before comparing with server-side stored session data. This enhances the
overall performance a bit, since sessions cookies would be checked for every
request to TYPO3's backend and frontend.

JWT handling in PHP is provided by 3rd party package
`firebase/php-jwt <https://packagist.org/packages/firebase/php-jwt>`__.


Impact
======

Session cookies `be_typo_user` and `fe_typo_user` can be pre-validated without
querying the database, which can filter invalid requests and might reduce the
enhances the overall performance a bit.

As a consequence session tokens are not sent "as is" anymore, but are
wrapped in a corresponding JWT message, which contains the following payload:

* `identifier` reflects the actual session identifier
* `time` reflects the time of creating the cookie (RFC 3339 format)


Affected installations
======================

All instances using TYPO3 v12 and having custom implementations handling `be_typo_user`
and `fe_typo_user` cookie values.


Migration
=========

Custom implementations handling `be_typo_user` or `fe_typo_user` cookies,
have to use the introduced method :php:`\TYPO3\CMS\Core\Session\UserSession::getJwt()`
instead of existing :php:`\TYPO3\CMS\Core\Session\UserSession::getIdentifier()`.


.. index:: Backend, Frontend, NotScanned, ext:core
