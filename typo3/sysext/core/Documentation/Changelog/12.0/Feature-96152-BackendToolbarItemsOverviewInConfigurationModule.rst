.. include:: /Includes.rst.txt

.. _feature-96152:

========================================================================
Feature: #96152 - Backend Toolbar items overview in configuration module
========================================================================

See :issue:`96152`

Description
===========

With :issue:`96041`, the registration of backend toolbar items had been
improved. Instead of being registered via :php:`$GLOBALS`, all implementations
of :php:`TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface` are now automatically
registered, while taking the defined :php:`index` into account.

To still allow administrators an overview of the registered toolbar items,
especially the final ordering, a corresponding list has been added to
the configuration module.

Impact
======

It's now possible for administrators to get an overview of all registered
toolbar items and the final ordering in the :guilabel:`Configuration` module.

.. index:: Backend, ext:lowlevel
