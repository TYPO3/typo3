.. include:: /Includes.rst.txt

.. _breaking-96041:

=================================================
Breaking: #96041 - Toolbar items: Register by tag
=================================================

See :issue:`96041`

Description
===========

Toolbar items implementing :php:`\TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface` are now automatically
registered by adding the tag :yaml:`backend.toolbar.item`, if :yaml:`autoconfigure`
is enabled in :file:`Services.yaml`.

Impact
======

The registration via :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems']` isn't evaluated anymore.

Affected Installations
======================

Every extension, that adds toolbar items via :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems']`
in its :file:`ext_localconf.php` file.

Migration
=========

Remove :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems']` from your :file:`ext_localconf.php` file.
If :yaml:`autoconfigure` is not enabled in your :file:`Configuration/Services.(yaml|php)`, add the tag :yaml:`backend.toolbar.item` to your toolbar item class.

Example:

..  code-block:: yaml

    VENDOR\Extension\ToolbarItem\YourAdditionalToolbarItem:
      tags:
        - name: backend.toolbar.item

.. index:: Backend, LocalConfiguration, PHP-API, FullyScanned, ext:backend
