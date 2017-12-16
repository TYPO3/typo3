
.. include:: ../../Includes.txt

=================================================================
Breaking: #76285 - Popup configuration is moved to TYPO3.settings
=================================================================

See :issue:`76285`

Description
===========

The popup window configuration has been moved to `TYPO3.settings`.

The following configuration options are not working anymore.

:javascript:`top.TYPO3.configuration.RTEPopupWindow.width`
:javascript:`top.TYPO3.configuration.RTEPopupWindow.height`
:javascript:`top.TYPO3.configuration.PopupWindow.width`
:javascript:`top.TYPO3.configuration.PopupWindow.height`


Impact
======

Width and height are not set correctly anymore in JavaScript context.


Affected Installations
======================

Extensions that use one of the mentioned configurations.


Migration
=========

The migration can be done with the following replacements.

:javascript:`top.TYPO3.configuration.RTEPopupWindow.width` to :javascript:`TYPO3.settings.Textarea.RTEPopupWindow.width`

:javascript:`top.TYPO3.configuration.RTEPopupWindow.height` to :javascript:`TYPO3.settings.Textarea.RTEPopupWindow.height`

:javascript:`top.TYPO3.configuration.PopupWindow.width` to :javascript:`TYPO3.settings.Popup.PopupWindow.width`

:javascript:`top.TYPO3.configuration.PopupWindow.height` to :javascript:`TYPO3.settings.Popup.PopupWindow.height`

.. index:: JavaScript, Backend
