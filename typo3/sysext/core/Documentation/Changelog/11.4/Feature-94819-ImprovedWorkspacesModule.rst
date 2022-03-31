.. include:: /Includes.rst.txt

============================================
Feature: #94819 - Improved Workspaces module
============================================

See :issue:`94819`

Description
===========

The workspaces module has been improved in usability:

For the initial loading of the module, the AJAX request has
to process less data as all information is already loaded with
the module.

A loading indicator is now visible during AJAX requests to
show editors that there is work in progress.

A dropdown is now used to choose between multiple workspaces,
which is especially useful when having multiple workspaces.

Administrators can edit workspace settings directly
from the module's docheader area.


Impact
======

The overall user experience has been improved and administrators
do not need to use the list module to manage workspaces anymore.

.. index:: Backend, ext:workspaces
