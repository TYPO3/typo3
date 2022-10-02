.. include:: /Includes.rst.txt

.. _breaking-96988:

============================================================
Breaking: #96988 - Global Option "allowLocalInstall" removed
============================================================

See :issue:`96988`

Description
===========

In previous TYPO3 version it was possible to disable the functionality to
install extensions from :file:`typo3conf/ext/`.

This was done by setting the global option
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXT']['allowLocalInstall']` to false.

The usefulness of this functionality was only a side-effect and has lost it even
more after the rise of the Composer Mode for TYPO3 Core.

In addition, this option is only useful in the Extension Manager which is now
protected with access for only "System Maintainers", only giving special users
the power to modify the extension installation process, making TYPO3 more
flexible than 15 years ago.

Impact
======

Toggling the option (which was enabled by default) has no effect anymore. It is
now always possible to install an extension available in :file:`typo3conf/ext/`
for system maintainers with the Extension Manager module for Non-Composer Mode
TYPO3 installations.

Affected Installations
======================

TYPO3 Installations in Non-Composer Mode having this option turned off, which
is very rare.

Migration
=========

It is recommended to set proper access rights and only give users
"System Maintainer" access which should modify the list of active extensions.

.. index:: Backend, FullyScanned, ext:extensionmanager
