.. include:: /Includes.rst.txt

========================================================
Feature: #83506 - Retrieve session data in TS conditions
========================================================

See :issue:`83506`

Description
===========

As the session API has been modified, it is no longer possible to access session data in TypoScript conditions by using
the formerly public property `sesData` of the frontend user object.

So now there is a more direct way using the keyword `session` with the same function:

.. code-block:: typoscript

   [globalVar = session:foo|bar = 1234567]

.. index:: Frontend, TypoScript
