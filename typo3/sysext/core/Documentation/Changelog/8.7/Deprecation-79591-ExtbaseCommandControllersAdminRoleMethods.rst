.. include:: ../../Includes.txt

====================================================================
Deprecation: #79591 - Extbase command controllers admin role methods
====================================================================

See :issue:`79591`

Description
===========

The methods :php:`CommandController->ensureAdminRoleIfRequested()` and
:php:`CommandController->restoreUserRole()` have been marked as deprecated.

All CLI scripts are now executed with administrator access rights, so this functionality is obsolete.


Impact
======

Calling any of the methods above will trigger a deprecation log warning.


Affected Installations
======================

Any installation with custom CLI Extbase Command Controllers using the methods above.


Migration
=========

Remove the affected lines where the methods are called, as they are not necessary anymore.

.. index:: CLI, ext:extbase, PHP-API
