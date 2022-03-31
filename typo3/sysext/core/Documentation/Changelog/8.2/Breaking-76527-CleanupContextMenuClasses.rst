
.. include:: /Includes.rst.txt

==============================================
Breaking: #76527 - Cleanup ContextMenu classes
==============================================

See :issue:`76527`

Description
===========

The classes of the ContextMenu (used in page tree) have been refactored.
The following classes have been removed:

* :php:`\TYPO3\CMS\Backend\ContextMenu\AbstractContextMenu`
* :php:`\TYPO3\CMS\Backend\ContextMenu\AbstractContextMenuDataProvider`
* :php:`\TYPO3\CMS\Backend\ContextMenu\Extdirect\AbstractExtdirectContextMenu`
* :php:`\TYPO3\CMS\Backend\ContextMenu\Renderer\AbstractContextMenuRenderer`


Impact
======

Extensions which use one of the classes above will stop working.


Affected Installations
======================

All installations with a 3rd party extension using one of the classes above.

.. index:: PHP-API, Backend
