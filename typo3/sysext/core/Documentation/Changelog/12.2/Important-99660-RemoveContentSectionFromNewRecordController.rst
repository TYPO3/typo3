.. include:: /Includes.rst.txt

.. _important-99660-1674251294:

==============================================================
Important: #99660 - Remove content area from new record wizard
==============================================================

See :issue:`99660`

Description
===========

The TYPO3 Backend comes with a distinction between "Content elements" and
other records: While content is managed using the specialized "Page" module,
the "List" module is the main management interface for other types of records.

Managing content elements from within the "List" module is not a good choice
for editors, the "Page" module should be used.

To foster this separation, the "Create new record" view reachable from within
the "List" module no longer allows to add "Content elements". As a side effect,
this avoids wrong or invalid default values of the "Column" (colPos) field.

.. index:: Backend, ext:backend
