.. include:: /Includes.rst.txt

==========================================================
Breaking: #87937 - TCA option "selicon_field_path" removed
==========================================================

See :issue:`87937`

Description
===========

The TCA option :php:`$GLOBALS['TCA'][$myTable]['ctrl']['selicon_field_path']` was removed.

The option allowed to show icons in select items when using :php:`$myTable` as a foreign table
in relations, and was bound to using :php:`selicon_field` as a legacy file (:php:`internal_type=file`).


Impact
======

It is now only possible to use :php:`selicon_field` in inline relations towards :php:`sys_file_reference`.
Setting the :php:`selicon_field_path` has no effect anymore and a PHP :php:`E_USER_DEPRECATED` error will be triggered.


Affected Installations
======================

Any TYPO3 installation with an extension providing TCA with :php:`selicon_field_path`.


Migration
=========

Remove the option :php:`selicon_field_path` and use a inline relation to file references in :php:`selicon_field` instead.

.. index:: TCA, PartiallyScanned, ext:core
