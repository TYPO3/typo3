.. include:: /Includes.rst.txt

=================================================================
Breaking: #92837 - Removed setting mod.web_layout.disableAdvanced
=================================================================

See :issue:`92837`

Description
===========

The TSconfig setting :typoscript:`mod.web_layout.disableAdvanced` has been used to disable the
"clear cache"-button in the page module.

Since this behaviour can be triggered through various other ways like the context menu or
by just saving the page record, this feature has been removed completely.


Impact
======

The setting :typoscript:`mod.web_layout.disableAdvanced` is not evaluated anymore and the "clear cache"-button
is always shown.


Affected Installations
======================

TYPO3 installations using the setting :typoscript:`mod.web_layout.disableAdvanced`.


Migration
=========

There is no migration possible.

.. index:: Backend, TSConfig, NotScanned, ext:backend
