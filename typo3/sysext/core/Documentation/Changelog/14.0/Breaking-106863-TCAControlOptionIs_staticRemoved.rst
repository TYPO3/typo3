..  include:: /Includes.rst.txt

..  _breaking-106863-1749629371:

========================================================
Breaking: #106863 - TCA control option is_static removed
========================================================

See :issue:`106863`

Description
===========

The TCA control option `is_static` has been removed, as it is no longer used or
evaluated anywhere in the TYPO3 Core.

Originally, `is_static` was introduced to mark certain database tables
(e.g. from :sql:`static_info_tables`) as containing static, non-editable
reference data. However, the TYPO3 ecosystem has evolved significantly over
the years, and the original purpose and necessity of is_static has become
outdated and irrelevant.

Modern TYPO3 projects rarely rely on static data tables, and better mechanisms
now exist for managing read-only or reference data. One can use TCA options
like `readOnly`, `editlock` or backend access control to limit editing if
needed. Eliminating legacy remnants also improves maintainability and lowers
the barrier for newcomers.


Impact
======

The option is no longer evaluated. It is automatically removed at runtime
through a TCA migration, and a deprecation log entry is generated to highlight
where adjustments are required.


Affected installations
======================

All installations using this option in their TCA configuration.


Migration
=========

Remove the `is_static` option from your TCA `ctrl` section.

..  index:: TCA, FullyScanned, ext:core
