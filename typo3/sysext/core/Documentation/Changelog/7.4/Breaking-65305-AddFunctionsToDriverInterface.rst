
.. include:: ../../Includes.txt

====================================================
Breaking: #65305 - DriverInterface has been extended
====================================================

See :issue:`65305`

Description
===========

The `getFolderInFolder()` and `getFileInFolder()` functions have been added to `DriverInterface`.


Impact
======

Any FAL driver extension will stop working due to the change in the interface.


Affected Installations
======================

Any installation with a custom FAL driver, like WebDAV or Dropbox.


Migration
=========

The functions `getFolderInFolder()` and `getFileInFolder()` must be added to the custom FAL driver.
A non-hierarchical driver needs to throw a "not implemented" exception when calling these functions.
