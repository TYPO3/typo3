..  include:: /Includes.rst.txt

..  _feature-106686-1747147977:

=============================================================
Feature: #106686 - Enhance file browsers with column selector
=============================================================

See :issue:`106686`

Description
===========

The file browsers used in the **file selector** (for example, in file fields)
and the **file link browser** (for example, for RTE links) have been enhanced
to support the column selector component.

This allows backend users to customize which columns are displayed
(for example, "alternative", "timestamp", and so on), improving usability and
aligning the interface with the standard File List module.

This enhancement is particularly useful in large file storages, making it
easier to locate recently updated files or identify large files more quickly.

Impact
======

The file selector and link browser interfaces now include a column selector.
This improves consistency across TYPO3 backend modules and provides users with
greater control over the visibility of file metadata.

No additional configuration is required for this feature.

Whether the column selector is shown is still determined by the user TSconfig
option :tsconfig:`options.file_list.displayColumnSelector`.

..  index:: Backend, ext:filelist
