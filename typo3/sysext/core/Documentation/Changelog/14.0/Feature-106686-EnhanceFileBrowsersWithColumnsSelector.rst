..  include:: /Includes.rst.txt

..  _feature-106686-1747147977:

==============================================================
Feature: #106686 - Enhance file browsers with columns selector
==============================================================

See :issue:`106686`

Description
===========

The file browsers used in the **file selector** (e.g., for file fields)
and the **file link browser** (e.g., for RTE links) have been enhanced to
support the column selector component.

This allows backend users to customize which columns are displayed
(e.g., "alternative", "timestamp", etc.), improving usability and aligning
the interface with the standard file list module.

This enhancement is especially useful in large file storages, making it easier
to find recently updated files or identify large files quickly.

Impact
======

The file selector and link browser interfaces now include a column
selector. This improves consistency across TYPO3 backend modules
and gives users better control over file metadata visibility.

This feature requires no additional configuration but remains controlled
by the UserTS option :typoscript:`options.file_list.displayColumnSelector`.

..  index:: Backend, ext:filelist
