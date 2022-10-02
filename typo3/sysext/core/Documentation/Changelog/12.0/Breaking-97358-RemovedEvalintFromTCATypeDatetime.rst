.. include:: /Includes.rst.txt

.. _breaking-97358:

============================================================
Breaking: #97358 - Removed eval=int from TCA type "datetime"
============================================================

See :issue:`97358`

Description
===========

With :issue:`97232` the new TCA type :php:`datetime` has been introduced. To
further improve the usage of the new dedicated TCA type and to further reduce
complexity in the configuration, the :php:`eval=int` option has now been
removed as well. All TCA type :php:`datetime` fields, which do not use a
native database type (:php:`dbType`) are now always handled with :php:`int`.

It is therefore recommended to represent them by an :sql:`integer` database
field. To allow negative timestamps - used for dates before 1970 - the
:sql:`integer` database fields are required to be defined as :sql:`signed`.
This means, the :sql:`unsigned` definition must be omitted.

.. note::

    TYPO3 automatically creates database fields for all TCA type
    :php:`datetime` columns, if those are not already manually
    defined in the corresponding extension's :file:`ext_tables.sql` file.

Impact
======

All TCA :php:`datetime` fields are now always handled with :php:`int`, as long
as no native database type is used.

TCA type :php:`datetime` was the last TCA type using :php:`eval=int`.
Therefore, the :php:`int` option is no longer evaluated by neither FormEngine
nor :php:`DataHandler`. This means, custom FormEngine elements, which do
currently rely on this option being evaluated in any way, have to implement
the necessary functionality by themselves now.

Affected Installations
======================

All installations which use TCA type :php:`datetime` columns
without a native database type (:php:`dbType`). Also installations, using
a non :php:`int` default value in TCA.

All installations, relying on evaluation of the :php:`eval=int` option
for their custom FormEngine elements.

Migration
=========

Remove :php:`eval=int` from any TCA column of type :php:`datetime`.

Migrate necessary functionality, related to TCA option :php:`eval=int`,
to your custom extension code, since FormEngine does no longer evaluate
this option.

Migrate :php:`default` values for TCA type :php:`datetime` fields
to :php:`int` (e.g. `''` to `0`).

Migrate corresponding database fields to :sql:`integer` where applicable.

..  code-block:: sql

    # Before
    CREATE TABLE tx_ext_my_table (
        datetime text
    );

    # After
    CREATE TABLE tx_ext_my_table (
        datetime int(11) DEFAULT '0' NOT NULL,
    );

.. note::

    In case the corresponding TCA field defines :php:`eval=null`, the
    :sql:`NOT NULL` definition must be omitted.

.. note::

    In case you don't need any manual configuration (e.g. a special default
    value), you can omit the definition of the database field, since TYPO3
    automatically creates those fields for TCA type :php:`datetime` columns.

.. index:: Backend, Database, PHP-API, TCA, NotScanned, ext:backend
