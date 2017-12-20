.. include:: ../../Includes.txt

===============================================================
Deprecation: #81763 - Deprecated language label for file rename
===============================================================

See :issue:`81763`

Description
===========

The language label `file_rename.php.submit` in `EXT:lang/Resources/Private/Language/locallang_core.xlf` has
been marked as deprecated.


Affected Installations
======================

Any TYPO3 extension using the deprecated label is affected.


Migration
=========

Add the label to the `locallang.xlf` of your extension and adjust the usage of the label.

.. index:: Backend, NotScanned, ext:lang