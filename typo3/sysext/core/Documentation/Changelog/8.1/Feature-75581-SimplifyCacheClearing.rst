
.. include:: /Includes.rst.txt

=========================================
Feature: #75581 - Simplify cache clearing
=========================================

See :issue:`75581`

Description
===========

The cache clearing system has been simplified by removing options in cache clear menu and install tool.

The cache clear menu in the backend contains now only two options:

* Flush frontend caches
  Clear frontend and page-related caches, like before.

* Flush all caches
  Clear all system-related caches, including the class loader, localization, extension configuration file caches and opcode caches. Rebuilding this cache may take some time.

Within the install tool the "Clear all cache" button will now also clear the opcode caches if possible.

.. index:: Backend
