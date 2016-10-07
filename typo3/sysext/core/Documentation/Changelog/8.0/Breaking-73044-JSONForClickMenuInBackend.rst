
.. include:: ../../Includes.txt

================================================
Breaking: #73044 - JSON for ClickMenu in Backend
================================================

See :issue:`73044`

Description
===========

The ClickMenu in the TYPO3 Backend now uses JSON to transport data between the server and the client.

Before, a proprietary <t3ajax> syntax with XML was used to transport the contents of the ClickMenu.


Impact
======

Using ClickMenu to implement a custom ClickMenu JavaScript handler instead of the default ClickMenu.js could result in
unexpected behaviour.


Affected Installations
======================

Installations with extensions that use custom ClickMenu.js behaviour.


Migration
=========

Adapt the custom code to handle JSON responses instead of XML responses.

.. index:: JavaScript, Backend
