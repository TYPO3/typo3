======================================================
Breaking: #72372 - Removed deprecated code from beuser
======================================================

Description
===========

The following methods have been removed from ``PermissionAjaxController``

``renderOwnername``
``renderPermissions``
``renderGroupname``


Impact
======

Using the methods above directly in any third party extension will result in a fatal error.


Affected Installations
======================

Instances which use calls to the methods above.


Migration
=========

Migrate your code that calls one of the methods to Fluid templates.

.. index:: php
