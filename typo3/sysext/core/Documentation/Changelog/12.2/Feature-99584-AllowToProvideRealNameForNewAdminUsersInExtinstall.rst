.. include:: /Includes.rst.txt

.. _feature-99584-1673985938:

==========================================================================
Feature: #99584 - Allow to provide name for new admin users in ext:install
==========================================================================

See :issue:`99584`

Description
===========

The field `realName` has been added to the "Create Administrative User" modal
in ext:install, so it is possible to provide the name of a new admin user.

The notice in the header of the model has been removed, since it is
superfluous now.

Impact
======

It is now possible to provide the field `realName`, when a new admin user
is created in ext:install.

.. index:: Backend, ext:install
