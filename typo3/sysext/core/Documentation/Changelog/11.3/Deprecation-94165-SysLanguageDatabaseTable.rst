.. include:: /Includes.rst.txt

========================================
Deprecation: #94165 - sys_language table
========================================

See :issue:`94165`

Description
===========

Since the introduction of site handling back in TYPO3 v9, available
languages and their associated information, for example locale, ISO code or the
navigation title are configured in the site configurations. As a consequence,
the :sql:`sys_language` table just duplicated this information and is therefore
now deprecated.

The Core internally does not longer rely on this table but fetches
necessary information from the site languages instead. This means, there
won't be any relation to the :sql:`sys_language` table, which allows to
define any kind of "ID" for the :php:`languageField` field of records, which
is usually :sql:`sys_language_uid`.

Also the site languages, used in site configurations, are now completely
independent of any :sql:`sys_language` record. Previously, when using the
site module to create or edit a site configuration, site languages could
only be added when a corresponding :sql:`sys_language` record existed.
This has now changed. The site configurations' `languages` field now
features a :guilabel:`Create new language` button, which allows to create a new
site language for this site configuration. Such newly created site
language will then also be available in the selector box of all other
site configurations. The ID for a new site language is always created
automatically (auto-increment). When selecting this site language in
another site configuration, most of the fields will now be prefilled.

.. note::

   When creating the first site configuration of a new installation, the
   languages selector box is empty, as new languages must be created via
   the :guilabel:`Create new language` button first. However, a default
   language (ID=0) record will always be added automatically.

Impact
======

Currently there is no direct impact. However, if your code relies on TYPO3
processing :sql:`sys_language`, you might have to adapt those places to use
site languages instead.

Affected Installations
======================

All installations which rely on TYPO3 processing the :sql:`sys_language`
table. For example for fetching available languages and their related
information.

Migration
=========

Adapt your code to always use site languages for fetching and processing
language related information.

For example, use the new TCA type `language`, introduced in :issue:`57082`,
instead of :php:`foreign_table => sys_language` for selecting a records'
language.

.. index:: Database, TCA, NotScanned, ext:core
