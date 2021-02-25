.. include:: ../../Includes.txt

=========================================================================
Feature: #93591 - Allow group id lookup in conditions with array operator
=========================================================================

See :issue:`93591`

Description
===========

In the backend and frontend the array of user group ids of the current backend user
is now available as backend.user.userGroupIds. In the frontend the the array of
user group ids of the current frontend user is available as frontend.user.userGroupIds.


Impact
======

This allows for a native Symfony Expression Syntax in TypoScript conditions, eg.

.. code-block:: typoscript

   [4 in frontend.user.userGroupIds]

   [2 in backend.user.userGroupIds]

With this syntax you can match backend user groups in the frontend without
an awkward "like" expression on the comma-separated list of user group ids.

.. index:: Backend, Frontend, TSConfig, TypoScript, ext:backend, ext:frontend
