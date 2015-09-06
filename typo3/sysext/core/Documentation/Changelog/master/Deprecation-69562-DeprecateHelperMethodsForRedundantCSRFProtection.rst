============================================================================
Deprecation: #69562 - Deprecate helper methods for redundant CSRF protection
============================================================================

Description
===========

The methods ``FormEngine::getHiddenTokenField`` and ``BackendUtility::getUrlToken`` were introduced as shortcuts to protect data manipulating entry points ``tce_db.php`` ``tce_file.php`` and ``alt_doc.php`` from CSRF attacks. These entry points are now replaced with
proper modules or routing, which are CSRF protected by default.


Impact
======

Third party code using  ``FormEngine::getHiddenTokenField`` or ``BackendUtility::getUrlToken`` will cause deprecation log entries.


Affected Installations
======================

Extensions using the above code.


Migration
=========

These method calls can safely be removed, when generating links to former entry points ``tce_db.php`` ``tce_file.php`` and ``alt_doc.php`` with the API method calls : ``BackendUtility::getModuleUrl('tce_db')``, ``BackendUtility::getModuleUrl('tce_file')`` or ``BackendUtility::getModuleUrl('record_edit')``.