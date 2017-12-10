.. include:: ../../Includes.txt

======================================================================
Breaking: #82377 - Option to allow uploading system extensions removed
======================================================================

See :issue:`82377`

Description
===========

The option to allow uploading additional extensions as system extensions in TYPO3 has been removed
without substitution.

Disclaimer: It is highly discouraged by the TYPO3 Core to modify anything within `typo3/sysext/`,
especially adding extensions, as typo3conf/ is the folder to add or override extensions.

If an administrator needs to do changes within `typo3/sysext/` it's at his/her own risk,
and should not be encouraged to be possible from TYPO3 itself.


Impact
======

The possibility to upload an extension into `typo3/sysext/` via the Extension Manager / TYPO3 Backend
interface is removed. System extensions can only be added or modified via the file system now.


Affected Installations
======================

TYPO3 instances having the option `$GLOBALS['TYPO3_CONF_VARS']['EXT']['allowSystemInstall']` enabled and do not
run in TYPO3's Composer Mode.

As this option was disabled by default for over 10 years, it is highly unlikely this change will
affect a regular instance.


Migration
=========

The mentioned option, if set, is automatically removed when accessing the Install Tool through
the :php:`SilentMigrationService`.

.. index:: LocalConfiguration, FullyScanned
