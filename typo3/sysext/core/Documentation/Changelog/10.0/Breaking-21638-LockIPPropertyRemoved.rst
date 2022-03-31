.. include:: /Includes.rst.txt

======================================================================
Breaking: #21638 - AbstractUserAuthentication::lockIP property removed
======================================================================

See :issue:`21638`

Description
===========

The IP-locking-functionality is extended from IPv4 only to now also support IPv6. A separate IpLocker-functionality was added.

The public property :php:`lockIP` in :php:`TYPO3\CMS\Core\Authentication\AbstractUserAuthentication` is now removed.
It usually shouldn't have been accessed directly and supported IPv4 only.


Impact
======

Extensions relying on :php:`lockIP` won't be able to perform their task anymore.
This might for example be the case when :php:`lockIP` was set dynamically, depending on the REMOTE_ADDR.


Affected Installations
======================

Every 3rd party extension depending on the formerly public :php:`lockIP` property is affected.


Migration
=========

Set :php:`lockIP` and :php:`lockIPv6` in :php:`TYPO3_CONF_VARS` - for FE or BE depending on the use case.
Use the new :php:`\TYPO3\CMS\Core\Authentication\IpLocker` API.

.. index:: Backend, Frontend, LocalConfiguration, NotScanned
