.. include:: /Includes.rst.txt

.. _breaking-96726:

==================================================================
Breaking: #96726 - RequestHandler functionality of Extbase removed
==================================================================

See :issue:`96726`

Description
===========

Extbase - TYPO3's MVC system has had a way to define "RequestHandlers", which
were primarily introduced back in 2010 to distinguish between Frontend Plugins,
Backend Modules, CLI commands and Fluid Widgets.

In TYPO3 v8, CLI commands have been migrated to Symfony Console.

Since TYPO3 v9, Backend Requests are not using RequestHandlers anymore.

In TYPO3 v10, the registration of custom RequestHandlers has been moved from
TypoScript to PHP files (during build time).

In TYPO3 v11, Fluid Widgets have been removed.

The only available support is for Frontend requests (plugin), which could have
been overridden by custom implementations.

From TYPO3 v12.0 onwards, Extbase Bootstrap for plugins is now calling the
Extbase dispatcher directly, without loading possible RequestHandlers anymore.

This change removes a layer for each request of a plugin, and thus, a layer
of indirection.

It is not possible anymore to implement custom RequestHandlers, as all related
functionality has been removed.

Impact
======

Registration of custom RequestHandlers will not have any effect anymore.

Affected Installations
======================

TYPO3 installations with extensions registering custom Extbase RequestHandlers.
This can be checked if an extension provides a
:file:`Configuration/Extbase/RequestHandlers.php` file or using the
extension scanner, which will report any usage of the now removed
:php:`\TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface`.

Migration
=========

It is recommended to avoid custom RequestHandlers, as their use case is
limited. For TYPO3 v12-only support, custom RequestHandlers and their
implementation can be fully removed and developed differently.

For Frontend plugins, it is still possible to use a different bootstrap
than the :php:`\TYPO3\CMS\Extbase\Core\Bootstrap` class, via TypoScript.

For backend modules, custom :php:`routeTargets` can be defined in the
module registration concept.

Using the Decorator pattern is usually good practice to achieve such
functionality.

.. index:: PHP-API, FullyScanned, ext:extbase
