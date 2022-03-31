.. include:: /Includes.rst.txt

=========================================================
Breaking: #92941 - "lockToIP" UserTsConfig option removed
=========================================================

See :issue:`92941`

Description
===========

The UserTsConfig setting :typoscript:`options.lockToIP` which allowed Backend
users or usergroups to only be valid when the user was accessing
TYPO3 with a certain IP address / range list, is removed.

Due to the IPv4/IPv6 dilemma "Happy Eyeballs" this feature only
has little use, and should be handled outside the Application instead,
but certainly not work on a per user/group basis.

This option was only used when the global option
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['enabledBeUserIPLock']` was enabled, and
could be disabled as system-wide setting where the UserTsConfig setting was
never evaluated anymore.

The global toggle was also removed, as it did not serve any other purposes.

Side note: From a TYPO3-internal request workflow this feature was never part of
the authentication process, as this usually happened after a successful user
login or session activation had happened, overruling any previous Authentication
Services registered. This was due to some ancient architectural decisions
18 years ago when this feature was added.


Impact
======

When the UserTsConfig setting :typoscript:`options.lockToIP` is set, it will not be
evaluated anymore.

When set, the global configuration flag will be automatically removed when the
Install Tool is accessed.


Affected Installations
======================

TYPO3 installations actively using this option in any UserTsConfig field or
file for Backend users or Backend user groups.


Migration
=========

If this functionality is still needed (mostly in Intranets), this needs to be
implemented as a third-party authentication service to validate an authenticated
user or group to be added to the current user / groups or via a custom PSR-15
middleware.

.. index:: TSConfig, PartiallyScanned, ext:backend
