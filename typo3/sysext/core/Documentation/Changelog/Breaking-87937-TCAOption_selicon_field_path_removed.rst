.. include:: ../../Includes.txt

==========================================================
Breaking: #87937 - TCA option "selicon_field_path" removed
==========================================================

See :issue:`87937`

Description
===========

The TCA option :php:`$GLOBALS['TCA'][$myTable]['ctrl']['selicon_field_path']` was removed.

The option allowed to show icons in select items when using :php:`$myTable` as a foreign table
in relations, and was bound to using `selicon_field` as a legacy file ("internal_type=file").


Impact
======

It is now only possible to use `selicon_field` in inline relations towards `sys_file_reference`.
Setting the `selicon_field_path` has no effect anymore and a deprecation warning will be triggered.


Affected Installations
======================

Any TYPO3 installation with an extension providing TCA with `selicon_field_path`.


Migration
=========

Remove the option `selicon_field_path` and use a inline relation to file references in `selicon_field` instead.

.. index:: TCA, PartiallyScanned, ext:core
