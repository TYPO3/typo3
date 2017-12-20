
.. include:: ../../Includes.txt

=================================================
Breaking: #77481 - Remove favicon from TBE_STYLES
=================================================

See :issue:`77481`

Description
===========

The configuration :php:`$GLOBALS['TBE_STYLES']['favicon']` has been removed.


Impact
======

The configuration :php:`$GLOBALS['TBE_STYLES']['favicon']` is not evaluated anymore.


Affected Installations
======================

Any installation using :php:`$GLOBALS['TBE_STYLES']['favicon']`.


Migration
=========

Define the favicon in the setting of EXT:backend in the extension manager.

.. index:: Backend, PHP-API
