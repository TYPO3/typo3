.. include:: /Includes.rst.txt

.. _important-104126-1714290385:

============================================================================================
Important: #104126 - Drop "typo3conf" directory from system status check and backend locking
============================================================================================

See :issue:`104126`

Description
===========

The directory :file:`typo3conf` is no longer needed in Composer Mode.
Checking for the existence of this directory is no longer performed in the
Environment and Install Tool.

Previously it would contain:

*  extensions (which are now Composer packages stored in :file:`vendor/`),
*  the configuration files (which are now part of the :file:`config/` tree),
*  language labels and some artifact states (now part of :file:`var/`).
*  a "backend lock" file (:file:`LOCK_BACKEND`)

The location to this file can be adjusted via the new configuration setting
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['lockBackendFile']`. See
:ref:`<feature-104126-1714290385>` for details on this setting and location.

By default, :file:`LOCK_BACKEND` is now located here:

*  `var/lock/` for Composer Mode
*  `config/` for Legacy Mode

.. index:: Backend, CLI, LocalConfiguration, ext:backend
