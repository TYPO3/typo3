.. include:: ../../Includes.txt

============================================================
Feature: #78695 - Set the session timeout for frontend users
============================================================

See :issue:`78695`

Description
===========

Previously it was possible to set the lifetime of a frontend user session via cookie,
which validated the session on the client-side, for the server-side there was only an option
for backend user sessions.

Setting the option :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['sessionTimeout']` is now possible
(via the Install Tool) to configure a global timeout for frontend sessions on the server-side.

.. index:: Frontend, LocalConfiguration
