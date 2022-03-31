.. include:: /Includes.rst.txt

========================================================
Deprecation: #85996 - ExtensionManager CommandController
========================================================

See :issue:`85996`

Description
===========

The following Extension Manager CLI commands have been reimplemented internally with Symfony console
commands:

* :bash:`extensionmanager:extension:install`, now :bash:`extension:activate`
* :bash:`extensionmanager:extension:uninstall`, now :bash:`extension:deactivate`
* :bash:`extensionmanager:extension:dumpclassloadinginformation`, now :bash:`dumpautoload`

The left-over command controller PHP class :php:`TYPO3\CMS\Extensionmanager\Command\ExtensionCommandController`
is not in use anymore, and therefore has been marked as deprecated.


Impact
======

Calling any of the commands within the PHP class will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations that extend the command controller directly in extensions are affected by this change.
Installations simply using the CLI entrypoint are not affected.


Migration
=========

* use :bash:`extension:activate` instead of :bash:`extensionmanager:extension:install`
* use :bash:`extension:deactivate` instead of :bash:`extensionmanager:extension:uninstall`
* use :bash:`dumpautoload` instead of :bash:`extensionmanager:extension:dumpclassloadinginformation`

In order to achieve the same functionality within custom PHP code, it is recommended to use the
underlying logic within the commands instead of calling or extending the command controller class.

.. index:: CLI, FullyScanned, ext:extensionmanager
