.. include:: /Includes.rst.txt

==========================================================================
Breaking: #88583 - Database field sys_language.static_lang_isocode removed
==========================================================================

See :issue:`88583`

Description
===========

The database field :sql:`static_lang_isocode` is a reference to a language within the third-party
extension `static_info_tables`. This was tightly coupled to TYPO3 Core until Site Handling was
introduced to add meaning and meta-data to a language on a per-site level.

The field is not in use by the TYPO3 Core anymore, so the database definition is removed as well.


Impact
======

Migrating to TYPO3 v10.0 will remove the field in the Database Analyzer.


Affected Installations
======================

Multilingual TYPO3 installations without the TYPO3 Extension `static_info_tables` but with usages of the
database field, which is very unlikely.


Migration
=========

The field can safely be removed in the Database Analyzer if it is not used by an extension.

If the field is still needed, it is recommended to install the extension `static_info_tables`.

If the data from the database field is used, it is recommended to fetch all metadata for a language
via the Site Configuration and the `SiteLanguage` API instead.

.. index:: Database, NotScanned
