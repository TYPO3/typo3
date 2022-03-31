.. include:: /Includes.rst.txt

===============================================================
Breaking: #92940 - Global option "lockBeUserToDBmounts" removed
===============================================================

See :issue:`92940`

Description
===========

The system-wide setting :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts']`
which was active by default, was used to allow any non-administrator to access
all pages in a TYPO3 installation without considering "Web Mounts" / "DB Mounts"
regardless of their permissions.

It was recommended to keep this setting turned on at any time due to several
security reasons.

This setting itself breaks TYPO3's internal permission concept and was never
implemented in all relevant places of TYPO3.

For this reason, the setting and all its usages are removed.


Impact
======

Activating or deactivating this option has no effect anymore as TYPO3 Core API
is working as this option was enabled at any time.


Affected Installations
======================

TYPO3 installations that have this option disabled in their system-wide
configuration in the :file:`LocalConfiguration.php` file.


Migration
=========

None, as this feature was removed for security purposes, re-adding this feature
is not recommended.

All usages in custom TYPO3 extensions can be removed.

.. index:: Backend, LocalConfiguration, FullyScanned, ext:core
