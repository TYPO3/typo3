.. include:: /Includes.rst.txt

====================================================
Deprecation: #81951 - Install Tool entry point moved
====================================================

See :issue:`81951`

Description
===========

The canonical entry point for accessing the install tool now is:

:file:`typo3/install.php`


Impact
======

Accessing :file:`typo3/install/` will still work and redirect to the new
location, but has been deprecated.


Affected Installations
======================

Every TYPO3 installation is affected.


Migration
=========

Change bookmarks or scripts from the old entry point to the new one.

.. index:: Backend, NotScanned
