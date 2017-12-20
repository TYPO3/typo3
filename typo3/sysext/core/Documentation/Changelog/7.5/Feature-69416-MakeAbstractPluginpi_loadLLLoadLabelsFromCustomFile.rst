
.. include:: ../../Includes.txt

========================================================================
Feature: #69416 - Plugins (AbstractPlugin) can load custom language file
========================================================================

See :issue:`69416`

Description
===========

:php:`AbstractPlugin::pi_loadLL()` takes an optional argument specifying path to a
language file. It allows placing language files in other paths like in Extbase
structure "Resources/Private/Language". Previously language file had to be
located in the directory set in the `scriptRelPath` property.


Impact
======

Possibility to put language label files in other paths.


.. index:: PHP-API, Frontend
