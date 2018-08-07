
.. include:: ../../Includes.txt

==========================================================================
Breaking: #75323 - Removed parameter entryPointPath from main applications
==========================================================================

See :issue:`75323`

Description
===========

The entry point `PHP` classes for :file:`index.php`, :file:`typo3/index.php` and so forth (called "Application classes")
now have a parameter not to define the path to the entry point but the number of subdirectories under the main
installation path, allowing to not specify the name of the path, but just the levels of subdirectories.

Subsequently, the methods `Bootstrap->baseSetup()`, `Bootstrap->redirectToInstallTool()` and
`SystemEnvironmentBuilder::run()` now expect an integer as parameter, instead of the path to the entry point script.


Impact
======

Calling one of the methods above with a string as parameter instead of an integer will fail because the calculation for PATH_site
which is the base for the whole installation will fail.


Affected Installations
======================

Any installation with custom entry points or custom extensions with separate entry points.


Migration
=========

Use the entry point level as integer, instead of the string, in your custom entry points. See
:php:`TYPO3\CMS\Backend\Http\Application` for an example.

.. index:: PHP-API, Backend
