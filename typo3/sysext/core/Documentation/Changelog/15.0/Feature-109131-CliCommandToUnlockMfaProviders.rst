..  include:: /Includes.rst.txt

..  _feature-109131-1792739915:

=======================================================================
Feature: #109131 - CLI command to unlock MFA providers of backend users
=======================================================================

See :issue:`109131`

Description
===========

After too many failed attempts, a multi-factor authentication (MFA) provider
of a backend user gets locked. Until now, such a lock could only be removed in
the backend, by the user after switching to that account.

The new CLI command :bash:`backend:user:mfa:unlock` unlocks all locked MFA
providers of a backend user. The user is given as uid, username or email
address:

..  code-block:: bash

    vendor/bin/typo3 backend:user:mfa:unlock 16
    vendor/bin/typo3 backend:user:mfa:unlock editor
    vendor/bin/typo3 backend:user:mfa:unlock editor@example.com

The command supports shell autocompletion for the user argument, which
suggests the usernames of all backend users having a locked provider.

Impact
======

Administrators can unlock MFA providers without access to the backend. In
contrast to deactivating MFA, the configured providers and their secrets are
kept. Providers that are not locked are not touched.

..  index:: CLI, Backend, ext:backend
