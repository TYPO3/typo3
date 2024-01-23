.. include:: /Includes.rst.txt

.. _important-ImpExp-1698313214:

========================================================
Important: #102130 - Optimizing T3D Import/Export module
========================================================

See :issue:`102130`

Description
===========

The Import/Export backend module is based on code that is
very hard to maintain, and is tightly coupled with both
the :php:`DataHandler` and :php:`RefIndex` routines.

To improve maintainability and reliability in these areas,
parts of the Import/Export module need to be refactored
as an ongoing effort.

The documentation of the Import/Export module now addresses
these challenges.

The ongoing refactoring may affect importing dumps from
older versions of TYPO3. Importing these dumps may lead
to missing data, also due to changes made
longer ago (i.e. the :sql:`pages_language_overlay` table no longer
exists, changes in workspaces, ...).

When performing an import of outdated TYPO3 versions,
thoroughly check the generated warnings and errors, so that
you can either recreate or upload missing data manually.

.. index:: Backend, NotScanned, ext:impexp
