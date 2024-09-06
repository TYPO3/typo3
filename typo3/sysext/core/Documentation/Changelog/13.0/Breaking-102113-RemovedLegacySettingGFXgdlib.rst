.. include:: /Includes.rst.txt

.. _breaking-102113-1696697947:

======================================================
Breaking: #102113 - Removed legacy setting 'GFX/gdlib'
======================================================

See :issue:`102113`

Description
===========

'GFX/gdlib' is a setting that enables or disables image manipulation
using GDLib, functionality used in :typoscript:`GIFBUILDER`, depending if
the host system did not provide GDLib functionality.

With this change, the configuration value 'GFX/gdlib' has been removed, and
TYPO3 will simply check for the :php:`GdImage` PHP class being available to
determine if it can be used.

Impact
======

TYPO3 now always enables GDLib functionality as soon as relevant GDLib classes
are found.


Migration
=========

The configuration value has been removed without replacement.

Custom code that relied on :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']`
should instead adopt the simpler check
:php:`if (class_exists(\GdImage::class))`.

.. index:: LocalConfiguration, FullyScanned, ext:core
