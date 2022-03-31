.. include:: /Includes.rst.txt

=====================================================================
Feature: #80581 - Render list of recently users that were switched to
=====================================================================

See :issue:`80581`

Description
===========

When a backend user with admin privileges switches to another user, the entered user is now stored in the uc. The users
stored in this list will be rendered into the user menu to allow quick switching to the recent users.


Impact
======

The user menu renders up to three users to which the currently logged in admin switched to.

.. index:: Backend
