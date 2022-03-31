.. include:: /Includes.rst.txt

=====================================================================================
Important: #77715 - No more password trimming for third-party authentication services
=====================================================================================

See :issue:`77715`

Description
===========

TYPO3's Authentication Service API allows third-party extensions
to handle custom login / password data to authenticate against identity brokers
via "OAuth", "LDAP" or "SAML2" now receive a given password (called `uident`)
directly as given from the input value.

Before TYPO3 v10 LTS, TYPO3's "AbstractUserAuthentication" object trimmed all incoming
usernames and passwords, and afterwards handed the sanitized input values over
to the Authentication Providers.

This made it impossible to ever have passwords that included spaces at
the beginning or the end of a given password.

This behaviour is now changed, and only affects Third-Party Authentication
providers - which can now decide to also trim passwords or keep them as is.

This logic is mostly handled within :php:`processLoginData()`. If the Third-Party
Authentication Provider is extending from Core's :php:`AuthenticationService` class and does
not override the method, then the behaviour will still be the same as before.

TYPO3's native Authentication Service still requires a password without spaces
at the beginning or end, however it is now up to the Authentication Service to
define what is possible or allowed.

.. index:: PHP-API, ext:frontend
