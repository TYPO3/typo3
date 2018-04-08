.. include:: ../../Includes.txt

======================================================
Feature: #84159 - Extract admin panel to own extension
======================================================

See :issue:`84159`

Description
===========

The admin panel has been extracted to a standalone extension. All admin panel specific code will be moved to the
extension removing cross-dependencies and enabling better scoping.


Impact
======

The admin panel can be completely uninstalled by deactivating the extension. To use the admin panel functionality the
extension has to be activated. Classes have been moved to the new extension and a class alias map for migration of
legacy code has been provided.

.. index:: Frontend, PHP-API, ext:frontend