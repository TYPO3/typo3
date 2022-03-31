.. include:: /Includes.rst.txt

=====================================================
Feature: #90298 - Improve user info in BE User module
=====================================================

See :issue:`90298`

Description
===========

The *Backend users* module has been improved by showing more details of TYPO3
Administrators and Editors:

- All assigned groups, including subgroups, are now evaluated
- All data which can be set in the backend user or an assigned group are now shown including allowed page types
- Read & write access to tables
- A new "detail view" for a TYPO3 Backend user has been added


Impact
======

Comparing users is more powerful now. It is now easier for TYPO3 Administrators
to check backend user permissions without the need to switch to the actual user
and test the behaviour.

.. index:: Backend, ext:beuser
