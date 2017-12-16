
.. include:: ../../Includes.txt

===================================================
Breaking: #68116 - Drop RTE.userLinks functionality
===================================================

See :issue:`68116`

Description
===========

Drop RTE.userLinks functionality from the ElementBrowser. The option was broken since 6.0 and has been removed now.


Impact
======

The special option won't show up in the ElementBrowser anymore.


Affected Installations
======================

All installations which use the option `RTE.userLinks`


Migration
=========

Use the newly added Tabbing API to add your custom link selection tab.
