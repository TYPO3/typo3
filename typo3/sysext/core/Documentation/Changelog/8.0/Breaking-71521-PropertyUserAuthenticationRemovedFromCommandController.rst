
.. include:: ../../Includes.txt

=============================================================================
Breaking: #71521 - Property userAuthentication removed from CommandController
=============================================================================

See :issue:`71521`

Description
===========

The property `$userAuthentication` was removed from the Extbase `CommandController` class and
has been migrated to the newly introduced `getBackendUserAuthentication()` method.


Impact
======

All command controllers deriving from `CommandController` with see a fatal error when accessing
properties or methods of the removed `$userAuthentication` property.


Migration
=========

Use the newly introduced `getBackendUserAuthentication()` method.

.. index:: PHP-API, ext:extbase
