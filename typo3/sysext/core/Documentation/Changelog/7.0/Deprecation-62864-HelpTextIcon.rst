
.. include:: /Includes.rst.txt

=============================================================
Deprecation: #62864 - BackendUtility->helpTextIcon deprecated
=============================================================

See :issue:`62864`

Description
===========

The function :code:`helpTextIcon()` in BackendUtility has been marked as deprecated.

Impact
======

The core does not use this functionality anymore.


Affected installations
======================

All installations which use the function :code:`helpTextIcon()`.

Migration
=========

Use :code:`BackendUtility::cshItem()` instead.


.. index:: PHP-API, Backend
