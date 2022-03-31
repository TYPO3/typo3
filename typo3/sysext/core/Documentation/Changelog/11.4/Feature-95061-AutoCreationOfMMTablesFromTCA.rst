.. include:: /Includes.rst.txt

=====================================================
Feature: #95061 - Auto creation of MM tables from TCA
=====================================================

See :issue:`95061`

Description
===========

TCA table column fields that define :php:`['config']['MM']` can omit specification of the
intermediate mm table layout in :file:`ext_tables.sql`. The TYPO3 database analyzer
takes care of proper schema definition.

This feature has been implemented to simplify developers life and to enable the TYPO3
core to handle those schema details since many extensions tend to specify incomplete
or broken mm table schema definitions when dealing with this complex area.

Extensions are strongly encouraged to drop :file:`ext_tables.sql` :sql:`CREATE TABLE`
definitions for those intermediate tables referenced by :php:`TCA` table columns. Dropping
these definitions allows the core to adapt and migrate definitions if needed.

Impact
======

Extension developers don't need to deal with :file:`ext_tables.sql` definitions of
"mm" tables anymore. The TYPO3 schema analyzer creates the intermediate schema depending
on :php:`TCA` field definition. The schema analyzer tries to apply default specifications
if possible. Single :file:`ext_tables.sql` definitions take precedence, though.

In practice, suppose the "local" side of a mm table is defined as such in TCA:

.. code-block:: php

    ...
    'columns' => [
        ...
        'myField' => [
            'label' => 'myField',
            'config' => [
                'type' => 'group',
                'foreign_table' => 'tx_myextension_myfield_child',
                'MM' => 'tx_myextension_myfield_mm',
            ]
        ],
        ...
    ],
    ...

Until now, a schema definition similar to this had to be in place in :file:`ext_tables.sql`:

.. code-block:: sql

    CREATE TABLE tx_myextension_myfield_mm (
        uid_local int(11) DEFAULT '0' NOT NULL,
        uid_foreign int(11) DEFAULT '0' NOT NULL,
        sorting int(11) DEFAULT '0' NOT NULL,

        KEY uid_local (uid_local),
        KEY uid_foreign (uid_foreign)
    );

This section can and should be dropped. Indicators a schema definition is affected by this:

* A table column TCA config defines :php:`MM` with :php:`type='select'`, :php:`type='group'`
  or :php:`type='inline'`.
* The "MM" intermediate table has *no* TCA table definition (!).
* :file:`ext_tables.sql` specifies a table with fields :sql:`uid_local` and :sql:`uid_foreign`.

The schema analyzer takes care of further possible fields apart from :sql:`uid_local` and
:sql:`uid_foreign`, like :sql:`sorting`, :sql:`sorting_foreign`, :sql:`tablenames`,
:sql:`fieldname` and :sql:`uid` if necessary, depending on "local" side of the TCA definition.

In general, in case an extension got that definition right up until now, the schema analyzer
should not drop or add any additional fields automatically when removing these sections from
:file:`ext_tables.sql`. Developers are strongly encouraged to drop affected :sql:`CREATE TABLE`
definitions from :file:`ext_tables.sql` and to verify the install tool schema migrator acts as
expected. The core takes care of these specifications from now on and may add adaptions or migrations
to streamline further details in the future.

.. index:: Database, ext:core
