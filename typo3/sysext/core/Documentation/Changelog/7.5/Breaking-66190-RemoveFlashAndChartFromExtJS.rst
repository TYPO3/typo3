
.. include:: ../../Includes.txt

====================================================
Breaking: #66190 - Remove flash and chart from ExtJS
====================================================

See :issue:`66190`

Description
===========

The flash and chart module is removed from ExtJS. In order to reduce ExtJS components this is a first step.


Impact
======

Extensions which use the flash and chart module from ExtJS will not work anymore.


Affected Installations
======================

Installations that use flash or chart module of ExtJS.


Migration
=========

Don't use cores ExtJS anymore, as we migrate away from it. Use other JS frameworks which implement such functionality for you.
