.. include:: /Includes.rst.txt

.. _breaking-96517:

====================================================
Breaking: #96517 - TMENU.collapse TypoScript removed
====================================================

See :issue:`96517`

Description
===========

The :typoscript:`collapse` TypoScript property of :typoscript:`TMENU` is removed
without substitution.

When set, active :typoscript:`TMENU` items previously linked to their parent page,
which was primarily a use case for :typoscript:`GMENU_LAYERS`, which was
removed in TYPO3 v6.0.

Impact
======

Setting this TypoScript option has no effect anymore.

Affected Installations
======================

TYPO3 installations with :typoscript:`TMENU` definitions having this option
set which is highly unlikely.

Migration
=========

Use a custom user function or the PSR-14 :php:`FilterMenuItemsEvent` event to modify
the menu items.

.. index:: Frontend, TypoScript, NotScanned, ext:frontend
