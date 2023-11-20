.. include:: /Includes.rst.txt

.. _deprecation-102440-1700638677:

===========================================================
Deprecation: #102440 - EXT:t3editor merged into EXT:backend
===========================================================

See :issue:`102440`

Description
===========

The JavaScript module specifier for modules shipped with the previous "t3editor"
extension has changed from :js:`@typo3/t3editor/` to
:js:`@typo3/backend/code-editor/`. The old specifier :js:`@typo3/t3editor/` is
still available, but deprecated.

The value of the existing TCA option `renderType` switched from `t3editor` to
`codeEditor`.


Impact
======

The module specifier :js:`@typo3/t3editor/` automatically maps to
:js:`@typo3/backend/code-editor/`. The TCA render type `t3editor` is
automatically migrated to `codeEditor`, triggering a deprecation log entry.


Affected installations
======================

All extensions using t3editor are affected.


Migration
=========

The JavaScript module namespace :js:`@typo3/t3editor/` maps to
:js:`@typo3/backend/code-editor/`.

Rewrite all TCA render types usages of `t3editor` to `codeEditor`.

.. index:: Backend, JavaScript, TCA, NotScanned, ext:t3editor
