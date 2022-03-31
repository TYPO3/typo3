.. include:: /Includes.rst.txt

======================================================
Breaking: #83153 - Migrated backend_layout.icon to FAL
======================================================

See :issue:`83153`

Description
===========

The existing database field "icon" for Backend Layouts put into the database, was previously a file upload field,
putting all icons under `uploads/media`. The field is migrated to the File Abstraction Layer (FAL), having
proper file relations like all other parts of TYPO3 core.


Impact
======

When working with the TCA for the backend_layout.icon field, sys_file_reference relations are now expected.
When querying the database table directly, icon only contains the number of references of this backend layout.


Affected Installations
======================

Installations with custom backend layout icons and, more specifically extensions dealing with the database
table directly.


Migration
=========

An upgrade wizard in the TYPO3 install tool moves all existing icons of backend_layouts from `uploads/media` to
`fileadmin/_migrated/backend_layouts/`.

For extensions directly working on the database table, the database access needs to be modified.

.. index:: Database, FAL, Backend, NotScanned
