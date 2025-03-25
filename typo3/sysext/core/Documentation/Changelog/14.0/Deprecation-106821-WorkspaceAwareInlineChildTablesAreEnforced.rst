..  include:: /Includes.rst.txt

..  _deprecation-106821-1749033014:

=======================================================================
Deprecation: #106821 - Workspace aware inline child tables are enforced
=======================================================================

See :issue:`106821`

Description
===========

TCA tables that are used as inline child table in a standard :php:`foreign_table`
relationship must be declared workspace aware if the parent table is workspace aware.

Impact
======

The TYPO3 core may automatically add :php:`versioningWS = true;` to the :php:`ctrl`
section of inline child tables. It will log a deprecation level message stating the
TCA definition should be adapted accordingly. This TCA definition should be added
even if the workspace extension is not loaded. The database schema analyzer will
add the required workspace related database columns.


Affected installations
======================

TYPO3 instances with extensions coming with inline relation where the parent table
is workspace aware and the child table is not workspace aware are affected.

A typical scenario are inline child tables attached to the :php:`tt_content` table.


Migration
=========

An automatic TCA migration is in place to scan TCA columns for this scenario and to
add :php:`versioningWS = true;` to the affected child tables. This should be added
to the TCA child table to satisfy the migration and suppress deprecation logs.

.. code-block:: php

    'ctrl' => [
        'versioningWS' => true,
    ],

Note the automatic migration **does not** detect children attached to inline fields
within :php:`type => 'flex'` (flex form) fields. Developers and integrators should still
declare such children workspace aware since in general the combination "parent is workspace aware"
and "child is not workspace aware" is not supported by the TYPO3 core in inline foreign_table
setups, no matter if the parent field is a database column or within a flex form field.

..  index:: TCA, NotScanned, ext:core
