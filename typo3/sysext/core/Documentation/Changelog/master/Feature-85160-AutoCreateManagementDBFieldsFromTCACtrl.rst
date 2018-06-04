.. include:: ../../Includes.txt

================================================================
Feature: #85160 - Auto create management DB fields from TCA ctrl
================================================================

See :issue:`85160`

Description
===========

The database schema analyzer automatically creates TYPO3 "management" related
database columns by reading a tables :php:`TCA` and checking the :php:`ctrl`
section for table capabilities.

Note this affects only basic columns like :php:`uid`, :php:`deleted` and language
handling related fields like :php:`sys_language_uid`, but **not** a tables main
business fields like a "title" field for a news extension. Those still have to
be defined by extension authors. No column definitions are created from the
:php:`columns` section of a tables :php:`TCA`.

However, :file:`ext_tables.sql` file can be stripped down to business fields.
For example, if a :php:`TCA` definition of a table specifies
:php:`$GLOBALS['TCA']['myTable']['ctrl']['sortby'] = 'sorting'`,
the core will automatically add the column :php:`sorting` with an appropriate
definition.

Field definitions in :file:`ext_tables.sql` take precedence over automatically
generated fields, so the core never overrides a manually specified column definition
from an :file:`ext_tables.sql` file.

These columns below are automatically added if not defined in :file:`ext_tables.sql`
for database tables that provide a :php:`$GLOBALS['TCA']` definition:

:php:`uid` and :php:`PRIMARY KEY`
  If removing the uid field from ext_tables.sql, the :php:`PRIMARY KEY` **must** be removed, too.

:php:`pid` and :php:`KEY parent`
  Column pid is :php:`unsigned` if the table is not workspace aware, the default
  index :php:`parent` includes :php:`pid` and :php:`hidden` as well as :php:`deleted`
  if the latter two are specified in :php:`TCA` :php:`ctrl`. The parent index creation
  is only applied if column :php:`pid` is auto generated, too.

:php:`['ctrl']['tstamp'] = 'fieldName'`
  Often set to :php:`tstamp` or :php:`updatedon`

:php:`['ctrl']['crdate'] = 'fieldName'`
  Often set to :php:`crdate` or :php:`createdon`

:php:`['ctrl']['cruser_id'] = 'fieldName'`
  Often set to :php:`cruser` or :php:`createdby`

:php:`['ctrl']['delete'] = 'fieldName'`
  Often set to :php:`deleted`

:php:`['ctrl']['enablecolumns']['disabled'] = 'fieldName'`
  Often set to :php:`hidden` or :php:`disabled`

:php:`['ctrl']['enablecolumns']['starttime'] = 'fieldName'`
  Often set to :php:`starttime`

:php:`['ctrl']['enablecolumns']['endtime'] = 'fieldName'`
  Often set to :php:`endtime`

:php:`['ctrl']['enablecolumns']['fe_group'] = 'fieldName'`
  Often set to :php:`fe_group`

:php:`['ctrl']['sortby'] = 'fieldName'`
  Often set to :php:`sorting`

:php:`['ctrl']['descriptionColumn'] = 'fieldName'`
  Often set to :php:`description`

:php:`['ctrl']['editlock'] = 'fieldName'`
  Often set to :php:`editlock`

:php:`['ctrl']['languageField'] = 'fieldName'`
  Often set to :php:`sys_language_uid`

:php:`['ctrl']['transOrigPointerField'] = 'fieldName'`
  Often set to :php:`l10n_parent`

:php:`['ctrl']['translationSource'] = 'fieldName'`
  Often set to :php:`l10n_source`

:php:`l10n_state`
  Column added if :php:`languageField` and :php:`transOrigPointerField` are set

:php:`['ctrl']['origUid'] = 'fieldName'`
  Often set to :php:`t3_origuid`

:php:`['ctrl']['transOrigDiffSourceField'] = 'fieldName'`
  Often set to :php:`l10n_diffsource`

:php:`['ctrl']['versioningWS'] = true` - :php:`t3ver_*` columns
  Columns that make a table workspace aware. All those fields are prefixed with
  :php:`t3ver_`, for example :php:`t3ver_oid` and :php:`t3ver_id`. A default
  index named :php:`t3ver_oid` to fields :php:`t3ver_oid` and :php:`t3ver_wsid` is
  added, too.


Impact
======

Extension developers can skip tons of "general" fields from extensions
:file:`ext_tables.sql` files.

.. index:: Database, TCA