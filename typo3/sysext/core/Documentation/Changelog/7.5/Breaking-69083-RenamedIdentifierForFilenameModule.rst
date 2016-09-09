
.. include:: ../../Includes.txt

=========================================================
Breaking: #69083 - Renamed identifier for filelist module
=========================================================

See :issue:`69083`

Description
===========

The filelist module was rewritten to use Extbase. Therefore the module identifier has been changed
from `file_list` to `file_FilelistList`.


Impact
======

All links pointing to the filelist module using the old identifier will break.


Affected Installations
======================

All installations that reference the filelist module by its old name.


Migration
=========

There is a upgrade wizard to change the backend user settings of users whose start module is the filelist module.
All other links to the module have to be changed manually to use `file_FilelistList` as module identifier.
