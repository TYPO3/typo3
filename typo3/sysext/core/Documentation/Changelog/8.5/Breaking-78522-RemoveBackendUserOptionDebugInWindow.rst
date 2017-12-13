.. include:: ../../Includes.txt

============================================================
Breaking: #78522 - Removed backend user option debugInWindow
============================================================

See :issue:`78522`

Description
===========

The backend user option `debugInWindow` was unused in the core and has been removed,
as the option of opening the debug information in a window was migrated already.


Impact
======

The setting is not available anymore in JavaScript under :javascript:`TYPO3.configuration`.


Affected Installations
======================

Any installation that uses the removed backend user option `debugInWindow`.

.. index:: Backend, JavaScript
