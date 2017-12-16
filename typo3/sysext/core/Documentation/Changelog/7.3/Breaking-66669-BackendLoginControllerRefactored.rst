
.. include:: ../../Includes.txt

=====================================================
Breaking: #66669 - Backend LoginController refactored
=====================================================

See :issue:`66669`

Description
===========

The backend login has been completely refactored and a new API has been introduced.
The openid form has been extracted and is now using the new API as well.


Impact
======

All former member variables of the  `LoginController` class have been removed or made protected, together with
some, now pointless, hooks and their related classes.

The deleted hooks are:

- `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginScriptHook']`
- `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/index.php']['loginFormHook']`

The removed class and its alias:

- `TYPO3\CMS\Rsaauth\Hook\LoginFormHook`
- `tx_rsaauth_loginformhook`


Affected Installations
======================

Any code manipulating the BE login.


Migration
=========

Use the new backend login form API.
