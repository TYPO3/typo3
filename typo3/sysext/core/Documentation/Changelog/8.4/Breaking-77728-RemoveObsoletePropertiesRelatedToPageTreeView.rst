.. include:: /Includes.rst.txt

====================================================================
Breaking: #77728 - Remove obsolete page tree and click menu settings
====================================================================

See :issue:`77728`

Description
===========

The following obsolete properties have been removed:

- :php:`FileSystemNavigationFrameController->doHighlight`

- :php:`ClickMenu->leftIcons`

The following user TS settings have been removed:

- :typoscript:`options.pageTree.disableTitleHighlight`

- :typoscript:`options.contextMenu.options.leftIcons`


Impact
======

Extensions which use one of the public properties above will throw a fatal error.
Setting above options in UserTSconfig will not impact the tree behaviour.


Affected Installations
======================

All installations with a 3rd party extension using one of the classes above.
All installations using one of the UserTSconfig settings above.


Migration
=========

No migration available.

.. index:: PHP-API, TSConfig, Backend
