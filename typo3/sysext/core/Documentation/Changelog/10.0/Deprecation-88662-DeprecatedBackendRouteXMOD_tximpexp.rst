.. include:: /Includes.rst.txt

============================================================
Deprecation: #88662 - Deprecated backend route xMOD_tximpexp
============================================================

See :issue:`88662`

Description
===========

The route identifier :php:`xMOD_tximpexp` (route `record/importexport`) pointing to
:php:`ImportExportController::mainAction` has been marked as deprecated. The class was previously responsible to handle
either the export or the import process, controlled by a query parameter.


Impact
======

Calling the route will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All 3rd-party extensions using the route :php:`xMOD_tximpexp` are affected.


Migration
=========

Depending on the task, either use :php:`tx_impexp_export` or :php:`tx_impexp_import`. Additionally, remove any
`tx_impexp[action]` query parameter.

.. index:: Backend, NotScanned, ext:impexp
