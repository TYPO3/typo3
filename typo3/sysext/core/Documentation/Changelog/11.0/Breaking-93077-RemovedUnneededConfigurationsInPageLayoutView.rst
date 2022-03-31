.. include:: /Includes.rst.txt

====================================================================
Breaking: #93077 - Removed unneeded configurations in PageLayoutView
====================================================================

See :issue:`93077`

Description
===========

The following TSconfig settings have been removed in favor of strong defaults and less configuration:

- :typoscript:`mod.web_layout.disableIconToolbar`
- :typoscript:`mod.web_layout.disableSearchBox`


Impact
======

The settings :typoscript:`mod.web_layout.disableIconToolbar` and :typoscript:`mod.web_layout.disableSearchBox` are
not evaluated anymore and the edit button and the search box are always shown in the page module.


Affected Installations
======================

TYPO3 installations using the settings :typoscript:`mod.web_layout.disableIconToolbar` or :typoscript:`mod.web_layout.disableSearchBox`.


Migration
=========

There is no migration possible.

.. index:: Backend, TSConfig, NotScanned, ext:backend
