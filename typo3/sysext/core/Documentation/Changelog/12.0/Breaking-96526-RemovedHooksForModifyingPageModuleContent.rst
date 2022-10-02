.. include:: /Includes.rst.txt

.. _breaking-96526:

==================================================================
Breaking: #96526 - Removed hooks for modifying page module content
==================================================================

See :issue:`96526`

Description
===========

The previously available hooks to modify the header
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook']`
and footer :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawFooterHook']`
content of the page module have been removed in favor of a new PSR-14
event :php:`TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent`.

The public method :php:`PageLayoutController->getModuleTemplate()` has been
removed as well, since it was only used for the removed hooks.

Impact
======

Registering any of the mentioned hooks does no longer have any
effect in TYPO3 v12.0+. The extension scanner will detect usages
as strong match.

The method :php:`PageLayoutController->getModuleTemplate()` is no longer
available and will therefore lead to PHP errors when called from extension
code. The extension scanner will detect usages as weak match.

Affected Installations
======================

TYPO3 installations using one of the mentioned hooks or calling
:php:`PageLayoutController->getModuleTemplate()` in custom extension
code.

Migration
=========

Replace the hooks with the new PSR-14
:doc:`ModifyPageLayoutContentEvent <../12.0/Feature-96526-PSR-14EventForModifyingPageModuleContent>` event.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
