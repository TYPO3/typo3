.. include:: /Includes.rst.txt

.. _feature-100268-1698511146:

===================================================================
Feature: #100268 - Provide full userdata in password recovery email
===================================================================

See :issue:`100268`

Description
===========

A new array variable :php:`{userData}` has been added to the password
recovery FluidEmail object. It contains the values of all fields belonging
to the affected frontend user.


Impact
======

It is now possible to use the :php:`{userData}` variable in the password
recovery FluidEmail to access data from the affected frontend user.

.. index:: Frontend, ext:felogin
