
.. include:: ../../Includes.txt

==========================================================
Breaking: #56133 - New BE user permission "Files: replace"
==========================================================

See :issue:`56133`

Description
===========

A new feature was introduced to replace files in the file list. For this feature an new permission was introduce "Files: replace".
This permission is now also checked when a BE user uploads a file with the same name.


Impact
======

BE users need the permission "Files: replace" before they are allowed to replace a file by uploading a file with the same name.


Affected Installations
======================

All installations.


Migration
=========

An upgrade wizard was added to set this permission for all BE users that already are allowed to write files as this was the old permissions check.
