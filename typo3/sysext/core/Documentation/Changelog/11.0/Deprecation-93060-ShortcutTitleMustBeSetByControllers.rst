.. include:: /Includes.rst.txt

.. _changelog-Deprecation-93060-ShortcutTitleMustBeSetByControllers:

===============================================================
Deprecation: #93060 - Shortcut title must be set by controllers
===============================================================

See :issue:`93060`

Description
===========

Previously the class :php:`ShortcutRepository` automatically generated a
shortcut title based on the given arguments. This generation was never reliable,
especially for custom extension code, since the repository
does not know about controller specific logic. Therefore, this functionality
has now been marked as deprecated. Backend controllers which add a shortcut button to
their module header are now required to also set the desired title.


Impact
======

Adding a new shortcut button without defining the :php:`$displayName` triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations using the shortcut button API without defining the
:php:`$displayName` property.


Migration
=========

Define the title with
:php:`TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton->setDisplayName()`.

.. index:: Backend, PHP-API, NotScanned, ext:backend
