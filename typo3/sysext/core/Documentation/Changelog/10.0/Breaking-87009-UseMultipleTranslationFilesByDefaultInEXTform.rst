.. include:: /Includes.rst.txt

========================================================================
Breaking: #87009 - Use multiple translation files by default in EXT:form
========================================================================

See :issue:`87009`

Description
===========

All :yaml:`translationFile` options in EXT:form setup and form definitions have been renamed to :yaml:`translationFiles`.

The following default translation files are now registered at index :yaml:`10` in all locations:

* :file:`EXT:form/Resources/Private/Language/locallang.xlf`
* :file:`EXT:form/Resources/Private/Language/Database.xlf`


Impact
======

Extending form setup or form definitions with additional translation files does not require adding the default translation files anymore.

The option :yaml:`translationFile` does not work anymore and must be migrated to :yaml:`translationFiles`.

Opening and saving a form with the form editor once also performs the migration of the corresponding form definition and makes it permanent.


Affected Installations
======================

All installations which use EXT:form and its :yaml:`translationFile` option.


Migration
=========

In your custom form configuration, migrate the single value :yaml:`translationFile` option to the multi value :yaml:`translationFiles` option.

Given that all default translation files of EXT:form are registered at index :yaml:`10` it is recommended to use a higher index for custom translation files.

Single file
-----------

Before:

.. code-block:: yaml

   translationFile: path/to/locallang.xlf

After:

.. code-block:: yaml

   translationFiles:
     20: path/to/locallang.xlf


Multiple files
--------------

Before:

.. code-block:: yaml

   translationFile:
     10: EXT:form/Resources/Private/Language/locallang.xlf
     20: path/to/locallang.xlf
     25: path/to/other/locallang.xlf

After:

.. code-block:: yaml

   translationFiles:
     20: path/to/locallang.xlf
     25: path/to/other/locallang.xlf

.. index:: YAML, NotScanned, ext:form
