
.. include:: ../../Includes.txt

====================================================================================
Deprecation: #65360 - Deprecate wrong class name used in PostProcessTree Signal call
====================================================================================

See :issue:`65360`

Description
===========

In DatabaseTreeDataProvider there is a PostProcessTree signal called via SignalSlot dispatcher.
The wrong class name `TYPO3\CMS\Core\Tree\TableConfiguration\TableConfiguration\DatabaseTreeDataProvider`
was used prior to this change. This class name has now been marked as deprecated.
The correct name is `TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider`


Impact
======

Wrong class name was used for the PostProcessTree signal.
The old one is now deprecated.


Affected installations
======================

All installations which have signals connected to the old/wrong class name
`TYPO3\CMS\Core\Tree\TableConfiguration\TableConfiguration\DatabaseTreeDataProvider`.


Migration
=========

* Use `TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider::class`
  instead of `TYPO3\CMS\Core\Tree\TableConfiguration\TableConfiguration\DatabaseTreeDataProvider`


.. index:: PHP-API
