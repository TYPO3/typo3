.. include:: ../../Includes.txt

========================================================
Deprecation: #85996 - ExtensionManager CommandController
========================================================

See :issue:`85996`

Description
===========

The following Extension Manager CLI commands have been reimplemented internally with Symfony console
commands:

* :shell:`extensionmanager:extension:install`, now :shell:`extension:activate`
* :shell:`extensionmanager:extension:uninstall`, now :shell:`extension:deactivate`
* :shell:`extensionmanager:extension:dumpclassloadinginformation`, now :shell:`dumpautoload`

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

* use :shell:`extension:activate` instead of :shell:`extensionmanager:extension:install`
* use :shell:`extension:deactivate` instead of :shell:`extensionmanager:extension:uninstall`
* use :shell:`dumpautoload` instead of :shell:`extensionmanager:extension:dumpclassloadinginformation`

In order to achieve the same functionality within custom PHP code, it is recommended to use the
underlying logic within the commands instead of calling or extending the command controller class.

.. index:: CLI, FullyScanned, ext:extensionmanager
