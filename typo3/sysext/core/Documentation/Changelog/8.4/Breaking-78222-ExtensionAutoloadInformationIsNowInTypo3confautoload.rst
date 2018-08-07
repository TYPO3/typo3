.. include:: ../../Includes.txt

==============================================================================
Breaking: #78222 - Extension autoload information is now in typo3conf/autoload
==============================================================================

See :issue:`78222`

Description
===========

To make clear that autoload information is not a cache,
the files have been moved from :file:`typo3temp` to :file:`typo3conf`.


Impact
======

TYPO3 deployments which do not take advantage of composer, might need some adaption
to also include the new location in :file:`typo3conf` in the list of to be synced files.


Affected Installations
======================

TYPO3 installation in non composer mode.


Migration
=========

Class loading information can be re-dumped by

* Activating or deactivating an extension
* Running the dump command from the command line
* In install tool (important actions)

The autoload files should never be deleted, but always only be re-dumped.

.. index:: PHP-API
