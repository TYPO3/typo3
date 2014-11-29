=============================================================
Deprecation: #62864 - BackendUtility->helpTextIcon deprecated
=============================================================

Description
===========

The function :php:`helpTextIcon()` in BackendUtility has been marked as deprecated.

Impact
======

The core does not use this functionality anymore.


Affected installations
======================

All installations which use the function :php:`helpTextIcon()`.

Migration
=========

Use :php:`BackendUtility::cshItem()` instead.
