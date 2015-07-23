========================================================
Deprecation: #69568 - Various FormEngine related methods
========================================================

Description
===========

The following methods have been deprecated and should not be used any longer:

* ``BackendUtility::getExcludeFields()``
* ``BackendUtility::getExplicitAuthFieldValues()``
* ``BackendUtility::getSystemLanguages()``
* ``BackendUtility::getRegisteredFlexForms()``
* ``BackendUtility::exec_foreign_table_where_query()``
* ``BackendUtility::replaceMarkersInWhereClause()``


Impact
======

Using those methods will throw a deprecation warning.


Affected Installations
======================

The impact is rather low in general since those methods were mostly internal in
the first place and only used within FormEngine scope. It is unlikely extensions
are affected by this change.


Migration
=========

If still used, extensions should switch to own solutions for those methods.