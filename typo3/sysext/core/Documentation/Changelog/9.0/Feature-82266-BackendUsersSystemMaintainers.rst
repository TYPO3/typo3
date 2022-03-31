.. include:: /Includes.rst.txt

==================================================
Feature: #82266 - Backend Users System Maintainers
==================================================

See :issue:`82266`

Description
===========

A new role for Backend Users is introduced - System Maintainers. These maintainers ("super admins")
are able to access the install tool modules from within the TYPO3 Backend, thus, the only place
to modify the system-wide configuration located in :php:`$TYPO3_CONF_VARS`, respectively
LocalConfiguration.php.

Extension management and language pack handling are also "system management" and thus restricted
to the new system management role.

The list of allowed admins that are assigned as system maintainers can only be done within the TYPO3
Install Tool or by modifying the new configuration option :php:`TYPO3_CONF_VARS[SYS][systemMaintainers]`.

If no system maintainer is set up, then all administrators are assigned the system maintainer role.

In Development context, all administrators are system maintainers as well.


Impact
======

It is now possible to only allow access the install tool from within the TYPO3 Backend for certain
Backend Users.

Registering Backend Modules can now be restricted to "systemMaintainer" access, so they are only
shown for selected administrators.

.. index:: Backend, LocalConfiguration
