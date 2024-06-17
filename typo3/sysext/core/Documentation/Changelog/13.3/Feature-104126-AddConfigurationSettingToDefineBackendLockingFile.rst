.. include:: /Includes.rst.txt

.. _feature-104126-1714290385:

===========================================================================
Feature: #104126 - Add configuration setting to define backend-locking file
===========================================================================

See :issue:`104126`

Description
===========

TYPO3 supports the ability to lock the backend for maintenance reasons. This
is controlled with a :file:`LOCK_BACKEND` file that was previously stored in
:file:`typo3conf/`.

With :ref:`<important-104126-1714290385>` this directory is no longer needed,
so now the location to this file can be adjusted via the new configuration setting
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['lockBackendFile']`.

When empty, it falls back to a file :file:`LOCK_BACKEND`, which is now stored
by default in:

*  `var/lock/` for Composer Mode
*  `config/` for Legacy Mode

If you previously manually maintained the :file:`LOCK_BACKEND` file (for example via
deployment or other maintenance automation), please either adjust
your automations to the new file location, or change the setting to the desired file location,
or at best use the CLI commands :file:`vendor/bin/typo3 backend:lock` and
:file:`vendor/bin/typo3 backend:unlock`.

The backend locking functionality is now contained in a distinct service class
:php:`TYPO3\CMS\Backend\Authentication\BackendLocker` to allow future flexibility.

When upgrading an installation to Composer Mode with a locked backend in effect,
please ensure your backend can remain locked by moving (or copying) the file to the new
location `var/lock/`.

Remember, if you want locked backend state to persist between deployments, ensure that the
used directory (`var/lock` by default) is shared between deployment releases.

Impact
======

The location for :file:`LOCK_BACKEND` to lock (and unlock) the backend can now be controlled
by maintainers of a TYPO3 installation, and has moved outside of :file:`typo3conf/` by default
to either `var/lock/` (Composer) or `config/` (Legacy).

.. index:: Backend, CLI, LocalConfiguration, ext:backend
