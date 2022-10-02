.. include:: /Includes.rst.txt

.. _feature-97821-1662456761:

==================================================================
Feature: #97821 - Option to configure primary actions in File List
==================================================================

See :issue:`97821`

Description
===========

This change provides the option to add more primary actions to the list view.
The list of actions to be displayed can be given in the TSconfig of the backend
user. The actions that can be added are `view`, `metadata`, `copy` and `cut`.

Example:

..  code-block:: typoscript

    options.file_list.primaryActions = view,metadata,copy,cut,delete

Impact
======

The actions available for the user by default become more clear.

.. index:: Backend, ext:filelist
