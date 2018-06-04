.. include:: ../../Includes.txt

=================================================================================
Important: #85160 - Non desctructive database schema changes in extension manager
=================================================================================

See :issue:`85160`

Description
===========

When loading or updating an extension using the backend extension manager, only
non destructive database schema changes are applied.

If for example a new version of an extension brings a **new** column, index or table
that does not exist locally yet, it will be **added**.

If the extension however for example **changes** the length of an existing field
or **removes** a column, index or table definition, these changes are
**not automatically applied** when loading or updating the extension. Administrators use
the database analyzer in Admin Tools -> Maintenance view to review and perform these
potentially destructive changes manually.

Additionally, missing fields or tables from other extensions are also added if an
extension is loaded, even if the extension that is loaded does not touch the
table in its :file:`ext_tables.sql` file.

.. index:: Database, TCA, ext:extensionmanager