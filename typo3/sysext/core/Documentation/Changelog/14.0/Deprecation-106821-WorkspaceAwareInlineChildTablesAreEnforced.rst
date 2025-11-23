..  include:: /Includes.rst.txt

..  _deprecation-106821-1749033014:

=======================================================================
Deprecation: #106821 - Workspace aware inline child tables are enforced
=======================================================================

See :issue:`106821`

Description
===========

TCA tables that are used as inline child tables in a standard
:php:`foreign_table` relationship must be declared as workspace aware if their
parent table is workspace aware.

Impact
======

The TYPO3 Core may automatically add `versioningWS = true` to the
`ctrl` section of inline child tables. In this case, a deprecation-level
log entry will be issued, indicating that the TCA definition should be updated
accordingly.

This TCA definition should be added even if the workspace system extension is
not loaded. The database schema analyzer will then automatically add the
required workspace-related database columns.

Affected installations
======================

TYPO3 instances with extensions that define inline relations where the parent
table is workspace aware but the child table is not are affected.

A typical scenario involves inline child tables attached to the
`tt_content` table.

Migration
=========

An automatic TCA migration scans TCA configurations for this scenario and adds
`versioningWS = true` to affected child tables. Developers should add this
declaration manually to their TCA to satisfy the migration and suppress
deprecation log messages.

..  code-block:: php

    'ctrl' => [
        'versioningWS' => true,
    ],

Note that the automatic migration **does not** detect children attached to
inline fields within `type => 'flex'` (flex form) fields. Developers and
integrators should still explicitly declare such child tables as workspace
aware.

In general, the combination of "parent is workspace aware" and "child is not
workspace aware" is not supported by the TYPO3 Core in inline
`foreign_table` setups—regardless of whether the parent field is a database
column or part of a flex form.

..  index:: TCA, NotScanned, ext:core
