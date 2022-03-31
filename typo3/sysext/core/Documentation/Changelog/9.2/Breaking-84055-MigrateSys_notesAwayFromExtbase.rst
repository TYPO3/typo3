.. include:: /Includes.rst.txt

======================================================
Breaking: #84055 - Migrate sys_notes away from extbase
======================================================

See :issue:`84055`

Description
===========

To simplify the rendering of sys_note records and improve the performance, the usage of `extbase` has
been removed from the extension `sys_note`.


Impact
======

The model :php:`TYPO3\CMS\SysNote\Domain\Model\SysNote` has been removed,
the repository :php:`TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository` now
returns a plain result instead of objects.

It is not possible anymore more to change the template path of the extension.


Affected Installations
======================

Any installation which relies on the repository and model or changed the template by using TypoScript.


Migration
=========

To change the rendering of notes, override the hook and return a modified output.

.. index:: Backend, PartiallyScanned, ext:sys_note
