.. include:: /Includes.rst.txt

.. _feature-100232-1679344020:

===============================================================
Feature: #100232 - Load additional stylesheets in TYPO3 Backend
===============================================================

See :issue:`100232`

Description
===========

It is now possible to load additional CSS files for the TYPO3
Backend Interface via regular :php:`$TYPO3_CONF_VARS` settings in a
projects' :file:`settings.php` (previously known as :file:`LocalConfiguration.php`)
file or in an extensions' :file:`ext_localconf.php`.

Previously this was done via the outdated :php:`$TBE_STYLES`
global array which has been deprecated.


Impact
======

By defining a specific stylesheet, a single CSS file or all CSS files
of a folder, extension authors can now modify the styling via:

:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'][my_extension] = 'EXT:myextension/Resources/Public/Css/myfile.css';`

:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'][my_extension] = 'EXT:myextension/Resources/Public/Css/';`

in their extensions' :file:`ext_localconf.php` file.

Site administrators can handle this in their :php:`settings.php` or :php:`additional.php` file.

.. index:: LocalConfiguration, ext:backend