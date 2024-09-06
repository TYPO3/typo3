.. include:: /Includes.rst.txt

.. _breaking-102924-1706178654:

===================================================================
Breaking: #102924 - Single Table Inheritance from fe_groups removed
===================================================================

See :issue:`102924`

Description
===========

Extbase ships with a feature called "Single Table Inheritance", to allow
multiple Extbase domain models reflecting one database table depending on a
specific value of a database field.

TYPO3 has the functionality enabled for the database tables :sql:`fe_users` and
:sql:`fe_groups`.

The respective default models, which do not make a lot of sense as models
depend on a specific domain, have been removed in previous TYPO3 versions.

For frontend user groups, the usage and the usefulness for TYPO3 to ship this out
of the box, has shown little impact. For this reason, the functionality has been
removed. Along with that, the database field :sql:`fe_groups.tx_extbase_type` and its
TCA definition as well as the Extbase configuration as a single table inheritance
option, has been removed.

The functionality for Single Table Inheritance in Extbase and also for frontend
users is working as before without any changes.

.. _Single Table Inheritance: https://en.wikipedia.org/wiki/Single_Table_Inheritance

Impact
======

Using the database field in custom code, or using Single Table Inheritance in
Extbase for frontend user groups will result in SQL and PHP errors.


Affected installations
======================

TYPO3 installations with custom extensions using Single Table Inheritance in
Extbase with frontend usergroups.


Migration
=========

If necessary, extension authors can add Single Table Inheritance in their own
extension for `fe_groups` by themselves.

* Add a database field :sql:`fe_groups.tx_extbase_type` in :file:`ext_tables.sql`
* Add TCA information in :file:`Configuration/TCA/Overrides/fe_groups.php` for the database field


.. index:: Database, TCA, NotScanned, ext:extbase
