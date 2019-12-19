.. include:: ../../Includes.txt

=======================================================
Feature: #89738 - Copy page access settings from parent
=======================================================

See :issue:`89738`

Description
===========

It is now possible to copy page access permissions from the parent page,
while creating new pages. This is possible, using :typoscript:`copyFromParent`
as value for one of the page TSconfig :typoscript:`TCEMAIN.permissions.*`
subkeys.

Example
=======

.. code-block:: typoscript

   TCEMAIN.permissions.userid = copyFromParent
   TCEMAIN.permissions.groupid = copyFromParent
   TCEMAIN.permissions.user = copyFromParent
   TCEMAIN.permissions.group = copyFromParent
   TCEMAIN.permissions.everybody = copyFromParent

.. index:: Backend, ext:core
