.. include:: /Includes.rst.txt

.. _deprecation-100335-1679998903:

==================================================
Deprecation: #100335 - TCA config MM_insert_fields
==================================================

See :issue:`100335`

Description
===========

The TCA option :php:`MM_insert_fields` has been marked
as deprecated and should not be used anymore.


Impact
======

Using :php:`MM_insert_fields` raises a deprecation level log message
during TCA cache warmup. Its functionality is kept in TYPO3 v12 but will
be removed in v13.


Affected installations
======================

There may be extensions that use this option when configuring database
MM relations. In most cases, the option can be removed. The migration
section gives more details.


Migration
=========

General scope: :php:`MM_insert_fields` is used in combination with "true"
database MM intermediate tables to allow many-to-many relations between
two tables for :php:`group`, :php:`select` and sometimes even :php:`inline`
type fields.

A core example is the :sql:`sys_category` to :sql:`tt_content`
relation, with :sql:`sys_category_record_mm` as intermediate table: The
intermediate table has field :sql:`uid_local` (pointing to a uid of
the "left" :sql:`sys_category` table), and :sql:`uid_foreign` (pointing to a
uid of the "right" :sql:`tt_content` table). Note this specific relation also
allows multiple different "right-side" table-field combinations, using the two
additional fields :sql:`tablenames` and :sql:`fieldname`. All this is configured
with TCA on the "left" and the "right" side table field, while table
:sql:`sys_category_record_mm` has no TCA itself. Rows within the intermediate
table are transparently handled by TYPO3 by the :php:`RelationHandler` and
extbase TCA-aware domain logic.

The :php:`MM_insert_fields` now allows to configure a hard coded value for
an additional column within the intermediate table. This is obsolete: There is
no API to retrieve this value again, having a "stable" value in an additional
column is useless. This config option should be removed from TCA
definition.

Note on the related option :php:`MM_match_fields`: This is important when an
MM relation allows multiple "right" sides. In the example above, when a category
is added to a :sql:`tt_content` record using the :sql:`categories` field, and when editing
this relation from the "right" side (editing a :sql:`tt_content` record), then this option
is used to select only relations for this :sql:`tt_content.categories` combination. The
TCA column :sql:`categories` thus uses :sql:`MM_match_fields` to restrict the
query. Note :sql:`MM_match_fields` is *not* set for the "left-side" :sql:`sys_category`
:sql:`items` fields, this would indicate a TCA misconfiguration.

Various extensions in the wild did not get these details right, and often simply
set *both* :php:`MM_insert_fields` and :php:`MM_match_fields` to the same values.
Removing :php:`MM_insert_fields` helps reducing confusion and simplifies this
construct a bit. Affected extensions can simply remove the :php:`MM_insert_fields`
configuration and keep the :php:`MM_match_fields`. Note the Core strives to further
simplify these options and :php:`MM_match_fields` may become fully obsolete in the
future as well.

.. index:: TCA, NotScanned, ext:core
