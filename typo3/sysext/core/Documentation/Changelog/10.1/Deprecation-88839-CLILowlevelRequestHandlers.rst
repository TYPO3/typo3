.. include:: /Includes.rst.txt

===================================================
Deprecation: #88839 - CLI lowlevel request handlers
===================================================

See :issue:`88839`

Description
===========

The interface :php:`\TYPO3\CMS\Core\Console\RequestHandlerInterface`
and the class :php:`\TYPO3\CMS\Core\Console\CommandRequestHandler` have been introduced in TYPO3 v7 to streamline
various entry points for CLI-related functionality. Back then, there were Extbase command requests and
`CommandLineController` entry points.

With TYPO3 v10, the only way to handle CLI commands is via the :php:`\TYPO3\CMS\Core\Console\CommandApplication` class which is
a wrapper around Symfony Console. All logic is now located in the Application, and thus, the interface and
the class have been marked as deprecated.


Impact
======

When instantiating the CLI :php:`\TYPO3\CMS\Core\Console\RequestHandler` class,
a PHP :php:`E_USER_DEPRECATED` error will be triggered.


Affected Installations
======================

Any TYPO3 installation having custom CLI request handlers wrapped via the interface or extending the
CLI request handler class.


Migration
=========

Switch to a Symfony Command or provide a custom CLI entry point.

.. index:: CLI, PHP-API, FullyScanned, ext:core
