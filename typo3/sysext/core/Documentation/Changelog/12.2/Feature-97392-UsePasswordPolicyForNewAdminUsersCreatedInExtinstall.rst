.. include:: /Includes.rst.txt

.. _feature-97392-1672220371:

================================================================================
Feature: #97392 - Use password policy for new admin users created in ext:install
================================================================================

See :issue:`97392`

Description
===========

The password for a new administrative backend user created using the install
extension does now consider the configurable password policy introduced in
:ref:`#97388 <feature-97388>`.


Impact
======

The globally configured password policy is now taken into account when a
new administrative backend user is created using the install extension.
Password policy requirements are shown below the password field and a message
is shown, if the new password does not meet the password policy requirements.

.. index:: Backend, ext:install
