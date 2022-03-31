.. include:: /Includes.rst.txt

===================================================================================
Breaking: #83638 - Redirect functionality moved from sys_domain to redirects module
===================================================================================

See :issue:`83638`


Description
===========

Records of type `sys_domain` previously provided the possibility to provide a redirect via the fields `redirectTo`,
`redirectHttpStatusCode` and `prepend_params`. The functionality has been moved from `sys_domain` to the
new `sys_redirect` database records.


Impact
======

The database fields `redirectTo`, `redirectHttpStatusCode` and `prepend_params` of table `sys_domain` have been removed.
Domain selection logic has been simplified to not consider these fields anymore.


Affected Installations
======================

All installations directly accessing the database fields `redirectTo`, `redirectHttpStatusCode` and `prepend_params` of
table `sys_domain` and having configured redirects in `sys_domain`.


Migration
=========

An upgrade wizard is provided to migrate `sys_domain` records with redirects to `sys_redirect` records.
If you directly accessed the named database fields above, change the queries accordingly to select the corresponding
`sys_redirect` records.

.. index:: Frontend, NotScanned
