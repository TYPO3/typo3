.. include:: /Includes.rst.txt

.. _feature-99608-1674053552:

=============================================================================
Feature: #99608 - Add password policy action to exclude validators in SU mode
=============================================================================

See :issue:`99608`

Description
===========

The new password policy action `UPDATE_USER_PASSWORD_SWITCH_USER_MODE` has been
added in order to allow administrators to exclude a password policy validator,
if the current user is in switch user mode.

The new password policy action is used in the global default password policy for
the `NotCurrentPasswordValidator`.


Impact
======

When the current backend user is in switch user mode, it is not validated,
if the new password equals the current user password in ext:setup.

.. index:: Backend, ext:core
