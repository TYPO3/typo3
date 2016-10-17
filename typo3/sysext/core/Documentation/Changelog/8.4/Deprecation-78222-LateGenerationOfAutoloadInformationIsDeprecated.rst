.. include:: ../../Includes.txt

===========================================================================
Deprecation: #78222 - Late generation of autoload information is deprecated
===========================================================================

See :issue:`78222`

Description
===========

If TYPO3 is in non-composer mode, it used to automatically dump extension class
loading information late during the bootstrap. This behavior is now deprecated.


Impact
======

TYPO3 installations in non-composer mode, now trigger a deprecation log entry
in case extension autoload information is missing late in the bootstrap.


Affected Installations
======================

TYPO3 installations in non-composer mode with deleted extension autoload information.


Migration
=========

Class loading information can be re-dumped by

* Activating or deactivating an extension
* Running the dump command from the command line
* In install tool (important actions)

The autoload files should never be deleted, but always only be re-dumped.

.. index:: PHP-API