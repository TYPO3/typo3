.. include:: /Includes.rst.txt

.. _feature-103521-1718028096:

=====================================================================================
Feature: #103521 - Change table restrictions UI to combine read and write permissions
=====================================================================================

See :issue:`103521`

Description
===========

The `tables_select` and `tables_modify` fields of the `be_groups` table store
information about permissions to read and write into selected database tables.

Due to TYPO3's internal behavior, when write permissions are granted for some
tables, those tables are also automatically available for reading.

To make managing table permissions much easier and more efficient for
integrators, the separate form fields for `Tables (listing) [tables_select]` and
`Tables (modify) [tables_modify]` have been combined into a single UI element.
This field now offers separate radio buttons to define which tables the backend
user group should have permission to read and / or write. This is done by
selecting one of the "No Access", "Read" or "Read & Write" options.

To further improve the user experience, it is also possible to use the
"Check All", "Uncheck All" and "Toggle Selection" options for each permission.

Under the hood, when these permissions are processed, they are still saved
separately in the `tables_select` and `tables_modify` columns in the
`be_groups` table, as they were before.

To render this new table view and handle its behavior, a dedicated form
renderType `tablePermission` has been introduced, which is now set for
the `tables_modify` column. The `tables_select` column has been changed
to TCA type `passthrough`.

The new form element is defined through:
:php:`\TYPO3\CMS\Backend\Form\Element\TablePermissionElement`.
It uses a dedicated data provider defined in:
:php:`\TYPO3\CMS\Backend\Form\FormDataProvider\TcaTablePermission`.
The JavaScript code is handled by a new web component:
:js:`@typo3/backend/form-engine/element/table-permission-element.js`.

When the :php-short:`\TYPO3\CMS\Backend\Form\FormDataProvider\TcaTablePermission`
data provider handles the configuration, it
reads table lists from both the `tables_select` and `tables_modify`
columns and combines them into a single array with unique table names.

Impact
======

Managing table permissions for backend user groups has been improved by
visually combining the `Tables (listing) [tables_select]` and
`Tables (modify) [tables_modify]` options, as well as by adding the
multi record selection functionality.

.. note::

    These changes might affect custom integrations and modifications made to
    the `tables_select` or `tables_modify` columns in the `be_groups` TCA.
    Integrators who have modified the configuration for these fields should
    verify if their code works and adapt it if needed.

.. index:: Backend, JavaScript, PHP-API, TCA, ext:core
