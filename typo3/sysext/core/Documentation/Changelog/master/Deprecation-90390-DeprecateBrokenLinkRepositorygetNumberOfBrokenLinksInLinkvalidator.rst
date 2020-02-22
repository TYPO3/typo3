.. include:: ../../Includes.txt

===============================================================================================
Deprecation: #90390 - Deprecate BrokenLinkRepository::getNumberOfBrokenLinks() in linkvalidator
===============================================================================================

See :issue:`90390`

Description
===========

The method :php:`BrokenLinkRepository::getNumberOfBrokenLinks()` is deprecated.


Impact
======

If third party extensions use this function, a `E_USER_DEPRECATED`
is triggered.


Affected Installations
======================

This only affects third party extensions which use this function. The
deprecated function is no longer used in the core.


Migration
=========

Use :php:`BrokenLinkRepository::isLinkTargetBrokenLink()` instead.

.. index:: Backend, NotScanned, ext:linkvalidator
