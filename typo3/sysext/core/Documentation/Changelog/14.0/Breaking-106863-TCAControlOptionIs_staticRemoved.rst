..  include:: /Includes.rst.txt

..  _breaking-106863-1749629371:

========================================================
Breaking: #106863 - TCA control option is_static removed
========================================================

See :issue:`106863`

Description
===========

The TCA control option
:php:`$GLOBALS['TCA'][$table]['ctrl']['is_static']` has been removed, as it is
no longer evaluated by the TYPO3 Core.

Originally, this option was introduced to mark certain database tables (for
example, from :sql:`static_info_tables`) as containing static, non-editable
reference data. Over time, the TYPO3 ecosystem has evolved, and the original
purpose of `is_static` has become obsolete.

Modern TYPO3 installations rarely rely on static data tables. Better mechanisms
now exist for managing read-only or reference data, such as the TCA options
`readOnly` and `editlock`, or backend access control. Removing this
legacy option improves maintainability and reduces complexity for newcomers.

Impact
======

The option `is_static` is no longer evaluated. It is automatically removed
at runtime by a TCA migration, and a deprecation log entry is generated to
indicate where adjustments are required.

Affected installations
======================

All TYPO3 installations defining
:php:`$GLOBALS['TCA'][$table]['ctrl']['is_static']` in their TCA configuration
are affected.

Migration
=========

Remove the `is_static` option from the `ctrl` section of your TCA
configuration.

..  index:: TCA, FullyScanned, ext:core
