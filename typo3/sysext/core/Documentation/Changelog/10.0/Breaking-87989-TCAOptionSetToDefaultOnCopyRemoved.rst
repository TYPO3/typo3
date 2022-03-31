.. include:: /Includes.rst.txt

========================================================
Breaking: #87989 - TCA option setToDefaultOnCopy removed
========================================================

See :issue:`87989`

Description
===========

The special TCA option :php:`$TCA[$tableName]['ctrl']['setToDefaultOnCopy']` is removed.

It allowed to reset a certain field to its default value when copying a record.


Impact
======

Having the setting set in TCA will trigger a PHP :php:`E_USER_DEPRECATED` error when building TCA.

Copying records with this TCA setting enabled, will now keep the copied state and avoid side-effects.


Affected Installations
======================

TYPO3 installations with active usage of `sys_action` or other extensions using this TCA setting.


Migration
=========

This option was only there for resetting some `sys_action` values to default, which
can easily be achieved by a hook if needed. If an extension author uses this setting,
this should be achieved with proper DataHandler hooks.

.. index:: TCA, PartiallyScanned, ext:core
