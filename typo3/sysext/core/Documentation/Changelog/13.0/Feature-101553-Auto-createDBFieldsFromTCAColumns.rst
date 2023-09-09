.. include:: /Includes.rst.txt

.. _feature-101553-1691166389:

=========================================================
Feature: #101553 - Auto-create DB fields from TCA columns
=========================================================

See :issue:`101553`

Description
===========

The TYPO3 v13 core strives to auto-create database columns derived from
:php:`TCA` :php:`columns` definitions without explicitly declaring them in
:file:`ext_tables.sql`.

Creating "management" fields like :sql:`uid`, :sql:`pid` automatically derived
from :php:`TCA` :php:`ctrl` settings is available for a couple of core versions
already, the core now extends this to single :php:`TCA` :php:`columns`.

As a goal, extension developers should not need to maintain a
:file:`ext_tables.sql` definition for casual table columns anymore, the file can
vanish from extensions and the core takes care of creating fields with sensible
defaults.

Of course, it is still possible for extension authors to override single
definitions in :file:`ext_tables.sql` files in case they feel the core does
not define them in a way the extension author wants: Explicit definition in
:file:`ext_tables.sql` always take precedence over auto-magic.


Impact
======

Extension authors should start removing single column definitions from
:file:`ext_tables.sql` for extensions being compatible with TYPO3 v13 and up.

If all goes well, the database analyzer will not show any changes since the core
definition is identical to what has been defined in :file:`ext_tables.sql` before.

In various cases though, the responsible class :php:`DefaultTcaSchema` may come
to different conclusions than the extension author. Those cases should be reviewed
by extension authors one-by-one: Most often, the core declares a more restricted
field, which is often fine. In some cases though, the extension author may
know the particular field definition better than the core default, and may decide
to keep the field definition within :file:`ext_tables.sql`.

Columns are auto-created for these :php:`TCA` :php:`columns` types:

* :php:`type = 'category'` - core v12 already
* :php:`type = 'datetime'` - core v12 already
* :php:`type = 'slug'` - core v12 already
* :php:`type = 'json'` - core v12 already
* :php:`type = 'uuid'` - core v12 already
* :php:`type = 'file'` - new with core v13
* :php:`type = 'email'` - new with core v13
* :php:`type = 'check'`- new with core v13


.. index:: TCA, ext:core, NotScanned
