
.. include:: ../../Includes.txt

============================================================================
Deprecation: #69562 - Deprecate helper methods for redundant CSRF protection
============================================================================

See :issue:`69562`

Description
===========

The method `BackendUtility::getUrlToken` has been introduced as shortcut to
protect data manipulating entry points `tce_db.php` `tce_file.php` and
`alt_doc.php` from CSRF attacks. These entry points have been replaced with
proper modules or routing, which are CSRF protected by default. With this
`BackendUtility::getUrlToken` is not needed anymore and therefore has been
marked as deprecated.


Impact
======

Third party code using  `BackendUtility::getUrlToken` will trigger deprecation
log entries.


Affected Installations
======================

Extensions using the above code.


Migration
=========

These method calls can safely be removed, when generating links to former entry
points `tce_db.php` `tce_file.php` and `alt_doc.php` with the API method
calls : `BackendUtility::getModuleUrl('tce_db')`, `BackendUtility::getModuleUrl('tce_file')`
or `BackendUtility::getModuleUrl('record_edit')`.


.. index:: PHP-API, Backend
