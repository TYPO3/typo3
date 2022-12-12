.. include:: /Includes.rst.txt

.. _breaking-98479-1664622195:

===============================================================
Breaking: #98479 - Removed file reference related functionality
===============================================================

See :issue:`98479`

Description
===========

With the introduction of the new TCA type :php:`file`, a couple of cross
dependencies have been removed, mainly related to FormEngine.

The :php:`customControls` hook option is not available for the new
TCA type :php:`file`. It has been replaced by the new PSR-14
:php:`CustomFileControlsEvent` for this use case.

The field :sql:`table_local` of table :sql:`sys_file_reference`: is no longer
evaluated by TYPO3 and has therefore been removed.

The following options are no longer evaluated for TCA type :php:`inline`:

- :php:`[appearance][headerThumbnail]`
- :php:`[appearance][fileUploadAllowed]`
- :php:`[appearance][fileByUrlAllowed]`

The following options are no longer evaluated for TCA type :php:`group`:

- :php:`[appearance][elementBrowserType]`
- :php:`[appearance][elementBrowserAllowed]`

A TCA migration is in place, removing those values from custom configurations.

Impact
======

Adding custom controls with the :php:`customControls` option does no longer
work for FAL fields.

Using the :sql:`table_local` field of table :sql:`sys_file_reference` does
no longer work and might lead to database errors.

Using one of the mentioned :php:`[appearance]` TCA options does no longer
have any effect.

Affected installations
======================

All installations making use of the :php:`customControls` option for FAL
fields, directly using the sql:`table_local` field of table
:sql:`sys_file_reference` or using one of the mentioned :php:`[appearance]`
TCA options for TCA type :php:`inline` and :php:`group` fields. Latter is
rather unlikley because the :php:`[appearance]` options of :php:`group`
had only effect in FAL context and the options have only been set internally
by the :php:`ExtensionManagementUtility->getFileFieldTCAConfig()` API method.

Migration
=========

Migrate corresponding user functions for the :php:`customControls` option to
a PSR-14 event listeners of the
:ref:`CustomFileControlsEvent <feature-98479-1664537749>`.

Remove any usage of the :sql:`table_local` field of
table :sql:`sys_file_reference` in custom extension code.

Remove the mentioned :php:`[appearance]` TCA options from your custom TCA
configurations.

.. index:: Backend, Database, FAL, PHP-API, TCA, PartiallyScanned, ext:backend
