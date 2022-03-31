.. include:: /Includes.rst.txt

===============================================================
Feature: #89229 - Cache Preset for Settings in Maintenance Area
===============================================================

See :issue:`89229`

Description
===========

The maintenance area available in TYPO3 Backend under "Admin Tools" => "Settings" now also allows to quickly switch
between Cache Backends for the Caching Framework.

This allows to select different settings without having to manually modify the :file:`LocalConfiguration.php` file.


Impact
======

Depending on a server setup, it might be easier to quickly see differences when running on a distributed Database system
(not localhost) or on a distributed file system to choose between caching options.

In addition, the most common caches are explained in what is stored there. Further configuration can be applied by
manually modifying the :file:`LocalConfiguration.php` settings file.

.. index:: LocalConfiguration, ext:core
