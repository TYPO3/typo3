..  include:: /Includes.rst.txt

..  _feature-107953-1761915787:

=====================================================================================
Feature: #107953 - Add "current logged-in users" filter to the "Backend Users" module
=====================================================================================

See :issue:`107953`

Description
===========

This feature introduces a new filter option **"Current logged-in users"**
to the **Backend Users** module. Administrators can instantly list all
backend accounts that are *currently* active.

The detection is based on the `lastlogin` timestamp in combination with
the global configuration value
`$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout']`.

Impact
======

It is now possible to filter backend users by those who are currently logged in.

..  index:: Backend, NotScanned
