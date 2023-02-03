.. include:: /Includes.rst.txt

.. _feature-77072-1671089957:

==============================================================================
Feature: #97390 - Use password policy for backend user password in ext:install
==============================================================================

See :issue:`77072`

Description
===========

The password used to create the backend user during install (GUI and setup
command) now considers the configurable password policy introduced in
:ref:`#97388 <feature-97388>`.

Impact
======

The globally configured password policy is now taken into account
when the backend user is created during the install process.

For each violation of the password policy a message will be
displayed to the user (GUI and setup command).

.. index:: Backend, ext:install
