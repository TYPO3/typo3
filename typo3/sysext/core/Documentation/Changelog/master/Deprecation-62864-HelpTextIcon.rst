=============================================================
Deprecation: #62864 - BackendUtility->helpTextIcon deprecated
=============================================================

Description
===========

The function helpTextIcon in BackendUtility is deprecated.

Impact
======

The core does not use this functionality anymore.


Affected installations
======================

All installations which use the function helpTextIcon.

Migration
=========

Use BackendUtility::cshItem instead.
