.. include:: /Includes.rst.txt

.. _feature-100268-1708278982:

==================================================================================
Feature: #103147 - Provide full userdata in password recovery email in ext:backend
==================================================================================

See :issue:`103147`

Description
===========

A new array variable :html:`{userData}` has been added to the password
recovery FluidEmail object. It contains the values of all fields from
the :sql:`fe_users` table belonging to the affected front-end user.


Impact
======

It is now possible to use the :html:`{userData}` variable in the password
recovery FluidEmail to access data from the affected front-end user.

.. index:: Backend, ext:backend
