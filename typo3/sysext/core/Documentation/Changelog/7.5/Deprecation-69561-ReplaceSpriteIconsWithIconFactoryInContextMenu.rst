
.. include:: ../../Includes.txt

==========================================================================
Deprecation: #69561 - Replace sprite icons with IconFactory in ContextMenu
==========================================================================

See :issue:`69561`

Description
===========

The `\TYPO3\CMS\Backend\ContextMenu\ContextMenuAction::$class` member variable is not
used anymore inside Core, therefore it has been marked as deprecated and will be removed with CMS 8.


Affected Installations
======================

Any installation using third party code, which accesses `ContextMenuAction::$class`.


Migration
=========

Remove any reference to `ContextMenuAction::$class`.


.. index:: PHP-API, Backend
