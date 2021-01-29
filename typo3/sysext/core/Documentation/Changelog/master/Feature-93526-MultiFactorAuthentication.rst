
.. include:: ../../Includes.txt

=============================================
Feature: #93526 - Multi-Factor Authentication
=============================================

See :issue:`93526`

Description
===========

TYPO3 is now capable of authentication via multiple factors, in short
"multi-factor authentication" or "MFA". This is sometimes also referred to
"2FA" as a 2-Factor Authentication process, where - in order to log in - the user
needs

1) "something you know" (= the password) and
2) "something you own" (= an authenticator device, or an authenticator app
   on mobile phones or desktop devices).

Read more about the concepts of MFA here https://en.wikipedia.org/wiki/Multi-factor_authentication

TYPO3 ships with some built-in MFA providers by default. But more importantly,
TYPO3 now provides an API to allow extension authors to integrate their own
MFA providers.

The API is designed in a way to allow providers to be used for TYPO3 Backend
Authentication or Frontend Authentication with a multi-factor step in-between.

TYPO3 Core currently provides the integration for the TYPO3 Backend, but will
fully support multi-factor authentication for the Frontend in future releases.

Impact
======

Managing MFA providers is currently accessible via the User Settings module in
the new tab called "Account security", which was previously called just
"Password". The Account security tab displays the current state, if MFA can
be configured, is activated or additional providers can be configured.

By default, MFA can be configured for every backend user. It is possible to
disable this field for editors via userTSconfig:

.. code-block:: typoscript

   setup.fields.mfaProviders.disabled = 1

The MFA configuration module displays all registered providers in an overview.

Included MFA providers
----------------------

TYPO3 Core includes two MFA providers:

1. Time-based one-time password (TOTP)

The most common MFA implementation. A QR-code is scanned (or alternatively,
a shared secret can be entered) to connect an Authenticator app such as Google
Authenticator, Microsoft Authenticator, 1Password, Authly or others to the
system and then synchronize a token, which changes every 30 seconds.

On each log-in, after successfully entering the password, the 6-digit code
shown of the Authenticator App must be entered.

2. Recovery codes

This is a special provider which can only be activated if at least one other
provider is active, as it's only meant as a fallback provider, in case the
authentication credentials for the "main" provider(s) are lost. It is encouraged
to activate this provider, and keep the codes at a safe place.

Setting up MFA for a backend user
---------------------------------

Each provider is displayed with its icon, the name and a short description.
In case a provider is active this is indicated by a corresponding label, next to
the providers' title. The same goes for a locked provider - an active provider,
which can currently not be used since the provider specific implementation
detected some unusual behaviour, e.g. to many false authentication attempts.
Furthermore does the configured default provider indicate this state with a
"star" icon, next to the providers title.

Each inactive provider contains a "Setup" button which opens the corresponding
configuration view. This view can be different depending on the MFA provider.

Each provider contains an "Edit / Change" button, which allows to adjust the
providers' setting. This view allows for example to set a provider as the
default (primary) provider, to be used on authentication. Note that the
default provider setting will be automatically applied on activation of the
first provider or in case it is the recommended provider for this user.

In case the provider is locked, the "Edit / Change" button changes its button
title to "Unlock". This button can therefore be used to unlock the provider.
This, depending on the provider to unlock, may require further actions by the
user.

Another view is the "Authentication view", which is displayed as soon as a user
with at least one active provider has successfully passed the username and
password mask.

As for the other views, it is up to the specific provider, used for the current
multi-factor authentication attempt, what content is displayed in this view.
In any case, if the user has further active providers, the view displays them
as "Alternative providers" in the footer. So the user can switch between all
activated providers on every authentication attempt.

All providers need to define a locking functionality. In case of the TOTP
and recovery code providers, this e.g. includes an attempts count. Therefore,
these providers are locked in case a wrong OTP was entered three times in a
row. The attempts count is automatically reset as soon as a correct OTP is
entered or the user unlocks the provider in the backend.

All Core providers also feature the "Last used" and "Last updated" information
which can be retrieved in the "Edit / Change" view.

**Administration of users' MFA providers**

If a user is not able to access the backend anymore, e.g. because all of their
active providers are locked, MFA needs to be disabled by an administrator for
this specific user.

Administrators are able to manage users' MFA providers in the corresponding
user record. The new `Multi-factor authentication` field displays a
list of active providers and a button to deactivate MFA for the user, or
only a specific MFA provider.

Note that all of these deactivate buttons are executed immediately, after
confirming the appearing dialog, and can't be undone.

The backend users listing in the backend user module also displays the current
MFA status ("enabled", "disabled" or "locked") for each user. This allows an
administrator a quick glance of the MFA usage of their users.

Via the System => Configuration admin module, it's possible to get an overview
of all currently registered providers in the installation. This is especially
helpful to find out the exact provider identifier, needed for some
userTSconfig options.

Configuration
-------------

**Enforcing MFA for users**

It seems reasonable to require MFA for specific users or user groups. This can
be achieved with :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['requireMFA']` which
allows 4 options:

* `0`: Do not require multi-factor authentication (default)
* `1`: Require multi-factor authentication for all users
* `2`: Require multi-factor authentication only for non-admin users
* `3`: Require multi-factor authentication only for admin users

To set this requirement only for a specific user or user group, a new
userTSconfig option `auth.mfa.required` is introduced. The userTSconfig option
overrules the global configuration.

.. code-block:: typoscript

   auth.mfa.required = 1

.. note::

   Requiring MFA has currently limited effect. Only an information is
   displayed in the MFA configuration module. This will change in future
   releases when this setting will require MFA being configured on accessing
   the TYPO3 Backend the first time. You are still already able to try it out.

**Allowed provider**

It is possible to only allow a subset of the available providers for some users
or user groups.

A new configuration option "Allowed multi-factor authentication providers" is
available in the users and user groups record in the "Access Rights/List" tab.

Note: Defining allowed MFA providers in a user record extends the settings
already done in user groups records the user is attached to.

There may surely be use cases in which just a single provider should be
disallowed for a specific user, which is however configured to be allowed in
one of the assigned user groups. Another use case is to disallow providers
for admin users, which simply do not have the "Access Rights/List" tab.
Therefore, the new userTSconfig option `auth.mfa.disableProviders` can be used.
It overrules the configuration from the "Access Rights/List", which means if
a provider is allowed in "Access Rights/List" but disallowed via userTSconfig,
it will be disallowed for the user or user group the TSconfig applies to.

This does not affect the remaining allowed providers from the
"Access Rights/List".

.. code-block:: typoscript

   auth.mfa.disableProviders := addToList(totp)

**Recommended provider**

To recommend a specific provider, :php:`$GLOBALS['TYPO3_CONF_VARS]['BE]['recommendedMfaProvider']`
can be used and is set to `totp` (Time-based one-time password) by default.

To set a recommended provider on a per user or user group basis, the new
userTSconfig option `auth.mfa.recommendedProvider` can be used, which overrules
the global configuration.

.. code-block:: typoscript

   auth.mfa.recommendedProvider = totp

TYPO3 Integration and API
-------------------------

.. important::

   The MFA API is still experimental and subject to change until v11 LTS,
   since we are looking forward to receive feedback, especially for custom
   use-cases, the API is not capable yet.

To register a custom MFA provider, the provider class has to implement the new
:php:`MfaProviderInterface`, shipped via a third-party extension. The provider
then has to be configured in the extensions' :file:`Services.yaml` or
:file:`Services.php` file with the :yaml:`mfa.provider` tag.

.. code-block:: yaml

   Vender\Extension\Authentication\Mfa\MyProvider:
      tags:
         - name: mfa.provider
           identifier: 'my-provider'
           title: 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:myProvider.title'
           description: 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:myProvider.description'
           setupInstructions: 'LLL:EXT:extension/Resources/Private/Language/locallang.xlf:myProvider.setupInstructions'
           icon: 'tx-extension-provider-icon'

This will register the provider `MyProvider` with the `my-provider` identifier.
To change the position of your provider the :yaml:`before` and :yaml:`after`
arguments can be useful. This can be needed if you e.g. like your provider to
show up prior to any other provider in the MFA configuration module. The
ordering is also taken into account in the authentication step while logging
in. Note that the user defined default provider will always take precedence.

If you dont want your provider to be selectable as a default provider, set the
:yaml:`defaultProviderAllowed` argument to `false`.

You can also completely deactivate existing providers with:

.. code-block:: yaml

   TYPO3\CMS\Core\Authentication\Mfa\Provider\TotpProvider: ~

The :php:`MfaProviderInterface` contains a lot of methods to be implemented by
the providers. This can be split up into state-providing ones,
e.g. :php:`isActive` or :php:`isLocked` and functional ones,
e.g. :php:`activate` or :php:`update`.

Their exact task is explained in the corresponding PHPDoc of the Interface files
and the Core MFA provider implementations.

All of these methods are recieving either the current PSR-7 Request object, the
:php:`MfaProviderPropertyManager` or both. The :php:`MfaProviderPropertyManager`
can be used to retreive and update the provider specific properties and
also contains the :php:`getUser` method, providing the current user object.

To store provider specific data, the MFA API uses a new database field `mfa`,
which can be freely used by the providers. The field contains of a JSON encoded
Array with each provider as array key. Common properties of such provider array
could be `active` or `lastUsed`. Since the information is stored in either the
`be_users` or the `fe_users` table, the context is implicit. Same goes for the
user, the providers deal with. It's important to have such generic field so
providers are able to store arbitrary data, TYPO3 does not need to know about.

To retrieve and update the providers data, the already mentioned
:php:`MfaProviderPropertyManager`, which is automatically passed to all
necessary provider methods, should be used. It is highly discouraged
to directly access the `mfa` database field.

.. index:: Backend, Frontend, PHP-API, ext:core
