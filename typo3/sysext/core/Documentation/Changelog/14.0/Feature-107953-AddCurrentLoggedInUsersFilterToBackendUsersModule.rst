..  include:: /Includes.rst.txt

..  _feature-107953-1761915787:

=====================================================================================
Feature: #107953 - Add "Current logged-in users" filter to the "Backend Users" module
=====================================================================================

See :issue:`107953`

Description
===========

A new filter option *Current logged-in users* has been added to the
:guilabel:`Administration > Users` module. This feature allows
administrators to quickly list all backend accounts that are currently active.

The detection mechanism is based on the :php:`lastlogin` timestamp in
combination with the global configuration value
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout']`, which defines the
maximum lifetime of backend user sessions.

.. note::
   The top-level backend modules were renamed in TYPO3 v14.
   The module now called :guilabel:`Administration` was previously named
   :guilabel:`System`, and the module now called :guilabel:`System` was
   previously named :guilabel:`Admin Tools`.
   For details, see:
   `Feature: #107628 – Improved backend module naming and structure
   <https://docs.typo3.org/permalink/changelog:feature-107628-1729026000>`_.

Impact
======

Administrators can now filter backend users by those who are currently logged in,
providing an immediate overview of active sessions directly within the
:guilabel:`Administration > Users` module.

..  index:: Backend, ext:beuser
