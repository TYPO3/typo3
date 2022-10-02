.. include:: /Includes.rst.txt

.. _breaking-96518:

==================================================================
Breaking: #96518 - ext_typoscript_*.txt files not included anymore
==================================================================

See :issue:`96518`

Description
===========

In previous TYPO3 versions, files named :file:`ext_typoscript_setup.txt` and
:file:`ext_typoscript_constants.txt` which could be placed into an extension's
root folder, were automatically included for all TypoScript evaluations.

This functionality stopped working, as the file ending `.typoscript`
has been unified since TYPO3 v8.

Impact
======

Contents of these files are not evaluated for TypoScript anymore.

Affected Installations
======================

TYPO3 installations with custom extensions including such files.

Migration
=========

Rename the files to :file:`ext_typoscript_setup.typoscript` and
:file:`ext_typoscript_constants.typoscript` which ensures compatibility
with all supported TYPO3 versions.

The file extension `.typoscript` was used since TYPO3 v8 and both versions (.txt
and .typoscript) have been working side-by-side since TYPO3 v8.

.. index:: TypoScript, NotScanned, ext:core
