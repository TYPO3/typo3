========================================================================
Deprecation: #62988 - Deprecate unused/non-unified DocumentTemplate code
========================================================================

Description
===========

Several functions within DocumentTemplate are not encouraged to be used anymore.

The hard-coded background image setting via :php:`$TBE_STYLES['background']` is removed and its usage is deprecated.

Additionally, the font-wrapping methods *rfw()* and *dfw()* are deprecated.
The according CSS was removed from the core.

The method *collapseableSection()*, which was used solely by the reports module for ages in a buggy
way, is also deprecated in favor of Bootstrap collapseables and localstorage.

Impact
======

The core does not use this functionality anymore.


Affected installations
======================

All installations which use the setting :php:`$GLOBALS['TBE_STYLES']['background']` or any of the functions:

* dfw()
* rfw()
* collapseableSection()

Migration
=========

* Use CSS directly instead of :php:`$GLOBALS['TBE_STYLES']['background']`
* Use the CSS class *text-muted* instead of the method :php:`dfw()`
* Use the CSS class *text-danger* instead of the method :php:`rfw()`
* Use HTML bootstrap classes, localStorage etc. instead of :php:`collapseableSection()`

