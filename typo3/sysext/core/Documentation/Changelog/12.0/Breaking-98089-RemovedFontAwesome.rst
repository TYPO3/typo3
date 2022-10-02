.. include:: /Includes.rst.txt

.. _breaking-98089-1659734321:

======================================
Breaking: #98089 - Removed FontAwesome
======================================

See :issue:`98089`

Description
===========

The node package `font-awesome` and the related CSS and font files have been
removed from the TYPO3 backend. This also includes the icon provider class
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider`.

The configuration option :php:`icon-class` of login providers has no effect
anymore.

Impact
======

Using the aforementioned icon provider to register icons is not possible
anymore. Also, any direct usage of :css:`fa-*` classes will not work anymore.

Affected installations
======================

All installations relying on FontAwesome are affected.

Migration
=========

Migrate to the `@typo3/icons` package if possible. If the TYPO3 installation
still requires FontAwesome, install the polyfill extension `fontawesome_provider`.

To install the extension via Composer, run the command
:bash:`composer require friendsoftypo3/fontawesome-provider`.

The extension will be available in TER soon as `fontawesome_provider <https://extensions.typo3.org/extension/fontawesome_provider>`__.

.. index:: Backend, PHP-API, NotScanned, ext:core
