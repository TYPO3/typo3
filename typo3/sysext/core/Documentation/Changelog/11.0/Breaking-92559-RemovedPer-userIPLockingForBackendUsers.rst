.. include:: /Includes.rst.txt

================================================================
Breaking: #92559 - Removed per-user IP locking for backend users
================================================================

See :issue:`92559`

Description
===========

TYPO3 has installation-wide options to allow so-called "IP Locking"
for Frontend User Sessions and Backend User Sessions ("lockIP").

Since TYPO3 v10, this feature is disabled by default, as some ISPs
allow for so-called Happy Eyeballs [https://en.wikipedia.org/wiki/Happy_Eyeballs]
to switch between IPv4 and IPv6, where a fixed IP Address per user session
cannot be guaranteed and is not proven as a useful measure for locking
a session anymore.

TYPO3 Core however had another specific BE-user feature, *if* the IP locking
features enabled for Backend users, it could be again *disabled*
for a specific user. This was previously built as a workaround
for users who did not have a specific IP address. This specific
feature, disabling IP locking for a specific Backend user, has
been removed as it lacks comprehensible use cases in the current
internet world, especially nowadays where home office and constantly
changing IP addresses are normal.

Impact
======

The additional checkbox when editing a backend user is removed,
including its Database field :sql:`be_users.disableIPlock` and its TCA
definition.

Accessing the field via a direct database request will result in a
SQL error. Accessing the TCA information will trigger a PHP notice.

If the system-wide setting is activated for backend users, it will apply
to any Backend user regardless of custom settings.

Affected Installations
======================

TYPO3 installations which use the IP locking mechanism for Backend
users (see :php:`$TYPO3_CONF_VARS[BE][lockIP]` and
:php:`$TYPO3_CONF_VARS[BE][lockIPv6]`) but explicitly deactivate
it for a specific backend user, which is highly unlikely.

The latter can be identified via a SQL query:

:sql:`SELECT count(uid) AS amount FROM be_users WHERE deleted=0 AND disableIPlock=1`.


Migration
=========

It is possible that this option was set by accident from administrators.
If not and some IP locking problems exist for certain backend users, it is
recommended to either remove the IP locking of backend users completely
via the Settings module (set system-wide options "lockIP" and "lockIPv6" to "0")
or add the functionality for your specific use case as custom extension, e.g. by
hooking into the authentication process and using the
:php:`\TYPO3\CMS\Core\Authentication\IpLocker` API.

.. index:: Backend, Database, TCA, FullyScanned, ext:core
