.. include:: /Includes.rst.txt

===============================================================
Feature: #94653 - Autocomplete attribute for PasswordViewHelper
===============================================================

See :issue:`94653`

Description
===========

Since password managers are frequently used by end users nowadays,
a password field can define the :html:`autocomplete` attribute,
which informs the users' password manager how to fill the corresponding
field. For example, creating a new password or filling in the current password.

See `MDN Allowing autocomplete`_ for a full list of possible attribute values.

To ease the use for integrators and developers, the attribute can now
directly be added as tag attribute to the :php:`PasswordViewHelper`.

Example:

.. code-block:: html

   <f:form.password name="newPassword" value="" autocomplete="new-password" />

   <!-- Output -->

   <input type="password" name="myNewPassword" value="" autocomplete="new-password" />


Impact
======

It's now possible to specify the :html:`autocomplete` attribute for the password
field through the :php:`PasswordViewHelper`.

.. _MDN Allowing autocomplete: https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/password#allowing_autocomplete

.. index:: Fluid, ext:fluid
