.. include:: /Includes.rst.txt

.. _important-106546-1786385411:

===========================================================
Important: #106546 - Tables that exist as views are ignored
===========================================================

See :issue:`106546`

Description
===========

The database analyzer no longer proposes any change for a table name that exists
as a view in the database. Views are introspected per connection and taken out of
both the current and the expected schema before they are compared.

A table declared in :file:`ext_tables.sql` or through TCA may exist as a view on
the connection it is mapped to, which is a common way to make data of another
system available to TYPO3. TYPO3 does not own such an object: altering or
dropping it would act on something the declaration does not describe, and
creating it fails outright, because the name is already taken by the view.

Impact
======

Tables that exist as views are left untouched by the database analyzer of the
install tool and by the schema update on the command line. Instances that worked
around this with a listener on
:php:`\TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent`
no longer need it for that purpose.

This does not make what is compared configurable in general - it only takes
views out of the comparison.

.. index:: Database, ext:core
