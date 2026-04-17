..  include:: /Includes.rst.txt

..  _deprecation-109517-1744105201:

==========================================================================================
Deprecation: #109517 - PSR-14 event :php:`TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent`
==========================================================================================

See :issue:`109517`

Description
===========

The PSR-14 event
that is dispatched in the :guilabel:`User Settings`
panel to allow injection of custom JavaScript methods has
been deprecated:

*  :php:`TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent`

With the integration of `EXT:setup` into `EXT:backend` (see
:ref:`important-109517-1744105200`) this event is now superseded
by:

*  :php:`TYPO3\CMS\Backend\Event\AddUserSettingsJavaScriptModulesEvent`

For better dual version compatibility, no deprecation is emitted
when using the legacy event location.

Migration to the new event can be done by just replacing its
new name. For details, see :ref:`important-109517-1744105200-AddJavaScriptModulesEvent`.

Impact
======

Using the old event will work as before in TYPO3 v14 but will
be removed with TYPO3 v15.0.

To keep listeners working, the new event
:php-short:`TYPO3\CMS\Backend\Event\AddUserSettingsJavaScriptModulesEvent`
must be utilized instead.

Affected installations
======================

Instances and extensions that register a PSR-14 listener on
:php-short:`TYPO3\CMS\Setup\Event\AddJavaScriptModulesEvent`.

Migration
=========

See :ref:`important-109517-1744105200-AddJavaScriptModulesEvent`.
The new event name and class namespace can be used with no further
functional changes.

..  index:: Backend, PHP-API, NotScanned, ext:backend
