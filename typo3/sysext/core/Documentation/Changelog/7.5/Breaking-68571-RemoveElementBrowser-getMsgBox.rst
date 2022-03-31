
.. include:: /Includes.rst.txt

===========================================================
Breaking: #68571 - Removed method ElementBrowser->getMsgBox
===========================================================

See :issue:`68571`

Description
===========

The removed method `getMsgBox` in `ElementBrowser` used table based styling.
The method has been removed since we have better means to display this kind of messages: FlashMessages or Callouts.


Impact
======

A fatal error will be thrown if the method `getMsgBox` is used.


Affected Installations
======================

Third party code using the removed method.


Migration
=========

Remove the call to the method and replace the message with a FlashMessage.


.. index:: PHP-API, Backend
