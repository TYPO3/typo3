
.. include:: ../../Includes.txt

==========================================================================
Breaking: #56746 - Add count methods and sort functionality to FAL drivers
==========================================================================

See :issue:`56746`

Description
===========

To improve the performance of the file list when showing (remote) storages with a lot of
files and folders the sorting and ordering needs to be done by the driver. Also the pagination of
the file list can be improved by moving the counting to the driver instead of fetching all files and
folders objects to count them.


Impact
======

Installations with custom FAL drivers will break after update.


Affected installations
======================

TYPO3 CMS 7 installations using custom FAL drivers.


Migration
=========

The custom FAL drivers need to be updated to be in line with the updated DriverInterface.

2 new functions need to be implemented:

- `countFoldersInFolder()`
- `countFilesInFolder()`

2 functions need to be extended with the parameters $sort and $sortRev:

- `getFilesInFolder(..., $sort, $sortRev)`
- `getFoldersInFolder(..., $sort, $sortRev)`


.. index:: PHP-API, FAL
