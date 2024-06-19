.. include:: /Includes.rst.txt

.. _feature-101553-1691166389:

=========================================================
Feature: #101553 - Auto-create DB fields from TCA columns
=========================================================

See :issue:`101553`

Description
===========

The TYPO3 v13 Core strives to auto-create database columns derived from
TCA :php:`columns` definitions without explicitly declaring them in
:file:`ext_tables.sql`.

Creating "management" fields like :sql:`uid`, :sql:`pid` automatically derived
from TCA :php:`ctrl` settings is available for a couple of Core versions
already, the Core now extends this to single TCA :php:`columns`.

As a goal, extension developers should not need to maintain a
:file:`ext_tables.sql` definition for casual table columns anymore, the file can
vanish from extensions and the Core takes care of creating fields with sensible
defaults.

Of course, it is still possible for extension authors to override single
definitions in :file:`ext_tables.sql` files in case they feel the Core does
not define them in a way the extension author wants: Explicit definition in
:file:`ext_tables.sql` always take precedence over auto-magic.

New TCA config option :php:`dbFieldLength`
------------------------------------------

For fields of type :php:`select` a new TCA config option :php:`dbFieldLength` has
been introduced. It contains an integer value that is applied to :sql:`varchar` fields
(not :sql:`text`) and defines the length of the database field. It will not be respected for
fields that resolve to an integer type. Developers who wish to optimize field
length can use :php:`dbFieldLength` for :php:`type=select` fields to increase or
decrease the default length the Core comes up with.

Example:

.. code-block:: php

    // will result in SQL text field
    'config' => [
        'itemsProcFunc => 'something',
    ],

    // will result in SQL varchar field length for 200 characters
    'config' => [
        'itemsProcFunc => 'something',
        'dbFieldLength' => 200,
    ],


Impact
======

Extension authors should start removing single column definitions from
:file:`ext_tables.sql` for extensions being compatible with TYPO3 v13 and up.

If all goes well, the database analyzer will not show any changes since the Core
definition is identical to what has been defined in :file:`ext_tables.sql` before.

In various cases though, the responsible class :php:`DefaultTcaSchema` may come
to different conclusions than the extension author. Those cases should be reviewed
by extension authors one-by-one: Most often, the Core declares a more restricted
field, which is often fine. In some cases though, the extension author may
know the particular field definition better than the Core default, and may decide
to keep the field definition within :file:`ext_tables.sql`.

Columns are auto-created for these TCA :php:`columns` types:

* :php:`type = 'category'` - Core v12 already
* :php:`type = 'datetime'` - Core v12 already
* :php:`type = 'slug'` - Core v12 already
* :php:`type = 'json'` - Core v12 already
* :php:`type = 'uuid'` - Core v12 already
* :php:`type = 'file'` - new with Core v13
* :php:`type = 'email'` - new with Core v13
* :php:`type = 'check'` - new with Core v13
* :php:`type = 'folder'` - new with Core v13
* :php:`type = 'imageManipulation'` - new with Core v13
* :php:`type = 'language'` - new with Core v13
* :php:`type = 'group'` - new with Core v13
* :php:`type = 'flex'` - new with Core v13
* :php:`type = 'text'` - new with Core v13
* :php:`type = 'password'` - new with Core v13
* :php:`type = 'color'` - new with Core v13
* :php:`type = 'radio'` - new with Core v13
* :php:`type = 'link'` - new with Core v13
* :php:`type = 'inline'` - new with Core v13
* :php:`type = 'number'` - new with Core v13
* :php:`type = 'select'` - new with Core v13
* :php:`type = 'input'` - new with Core v13

See :ref:`Breaking: DateTime column definitions <breaking-99937-1691166389>`
for a change in the :sql:`datetime` column definition calculation.

Also see :ref:`Important: About database error "row size too large" <important-104153-1718790066>`
for limits imposed by MySQL / MariaDB on table length.

.. index:: TCA, ext:core, NotScanned
