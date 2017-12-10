.. include:: ../../Includes.txt

==============================================
Breaking: #82445 - Pages and page translations
==============================================

See :issue:`82445`

Description
===========

The database table `pages_language_overlay` has been obsoleted in the core and is
not used and updated anymore. Page translation records are now handled in the "pages"
table directly.


Impact
======

This change has a huge impact on page and page translation handling, especially
on database record level:

* Table "pages_language_overlay" is no longer read by core code
* Records in "pages_language_overlay" are no longer updated by core code
* Records in "pages_language_overlay" are no longer shown in the backend
* Table and TCA definition for "pages_language_overlay" will be dropped in v10
* Queries to table "pages" should now observe the "sys_language_uid" field to
  fetch default language records only. A casual case for this are tree traversal
  queries for children or rootline fetching. If additional restrictions are not
  added, the query result may return page translations along the default language row.
* Existing inline relations with "foreign_table" and "foreign_field" and "foreign_table_field"
  on a "pages_language_overlay" TCA are migrated to "pages". This works well for
  typical FAL relations like the default "media" field.
* Complex TCA relations with "inline" "group" that use an "MM" table in TCA
  not automatically get their relation record rows migrated. Configurations
  like these are seldom and need manual migration steps depending on their
  TCA configuration when upgrading.


Affected installations
======================

Single language instances are not affected. For sites with translations and
non-empty "pages_language_overlay" table, the main data merging is done with
upgrade wizards, but it may happen that TypoScript and extensions may need
adaptions, for instance if they write and read data from "pages" or
"pages_language_overlay" directly.


Migration
=========

The following backwards-compatibility are met until TYPO3 v10.0:

* The TCA definition for "pages_language_overlay" is kept as part of handling extensions supporting v8 and v9
* The database table "pages_language_overlay" is kept as is, but not updated anymore by core
* A database field within "pages" is keeping the old pages_language_overlay record UID
* An upgrade wizard merges records from "pages_language_overlay" into "pages"
* An upgrade wizard adapts "be_groups" access restrictions for "pages_language_overlay" towards "pages"

.. index:: Backend, Database, PHP-API, TCA, NotScanned
