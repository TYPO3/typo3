.. include:: /Includes.rst.txt

.. _breaking-96154:

================================================================
Breaking: #96154 - Deprecated Shortcut API functionality removed
================================================================

See :issue:`96154`

Description
===========

In TYPO3 v11 the Shortcut API was reworked to clean up the codebase and
to align with the new Backend routing. Therefore, previously deprecated
functionality has now been removed.

The following methods have been removed:

- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->makeShortcutIcon()`
- :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->makeShortcutUrl()`
- :php:`\TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->getGetVariables()`
- :php:`\TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->getModuleName()`
- :php:`\TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->getSetVariables()`
- :php:`\TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->setGetVariables()`
- :php:`\TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->setModuleName()`
- :php:`\TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->setSetVariables()`

The following ViewHelper has been removed:

- :html:`<f:be.buttons.shortcut>`

The following functionality has been removed:

- The automatic fallback, calculating a title for a new shortcut, based on the module
- The automatic fallback, calculating a description for existing shortcuts, based on the module
- The automatic fallback, determining the route identifier, based on the route path
- The automatic fallback, determining the route identifier, based on the module name
- The automatic fallback, determining the route identifier, based on the route parameter

Impact
======

Calling one of the removed methods or using the ViewHelper will most likely
raise a PHP fatal level error.

When using an existing shortcut without a title, the fallback "Shortcut"
will be displayed.

When adding a :php:`ShortcutButton`, without providing a valid route
identifier or a display name, an exception will be triggered.

Affected Installations
======================

All installations using one of the mentioned methods or the ViewHelper.

All installations relying on one or multiple of the mentioned fallbacks.

Migration
=========

Remove any usage to the mentioned methods or the ViewHelper.

Properly add the :php:`ShortcutButton` with the required information.

.. index:: Backend, PHP-API, PartiallyScanned, ext:backend
