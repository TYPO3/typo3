.. include:: /Includes.rst.txt

.. _breaking-102931-1706198332:

==============================================
Breaking: #102931 - Removed hook in GifBuilder
==============================================

See :issue:`102931`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_gifbuilder.php']['gifbuilder-ConfPreProcess']`
has been removed.

This hook was solely introduced in TYPO3 v3.8.0 for a specific use case which
isn't needed anymore, and thus removed.

At the same time the whole :php:`GifBuilder` class is now strictly typed.


Impact
======

PHP code utilizing this hook will not be executed anymore.


Affected installations
======================

TYPO3 installations with extensions utilizing this hook, which is highly unlikely.

Any usages can be found with the Extension Scanner in the Install Tool.


Migration
=========

It is recommended to hand in custom configuration already into GifBuilder
directly, and remove any usages to the hook in custom extension code.

.. index:: PHP-API, FullyScanned, ext:frontend
