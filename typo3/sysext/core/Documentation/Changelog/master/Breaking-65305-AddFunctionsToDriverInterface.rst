====================================================
Breaking: #65305 - DriverInterface has been extended
====================================================

Description
===========

The getFolderInFolder and getFileInFolder functions were added to the DriverInterface.


Impact
======

Any FAL driver extension will stop working.


Affected Installations
======================

Any installation with a custom FAL driver, like WebDAV or Dropbox.


Migration
=========

The functions getFolderInFolder and getFileInFolder must be added to the custom FAL driver.
A Non-hierarchical driver needs to throw a "not implemented" exception when calling these functions.
