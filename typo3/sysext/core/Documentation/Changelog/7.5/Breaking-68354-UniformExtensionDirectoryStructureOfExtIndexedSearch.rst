
.. include:: /Includes.rst.txt

==============================================================================
Breaking: #68354 - Uniform extension directory structure of EXT:indexed_search
==============================================================================

See :issue:`68354`

Description
===========

The directory structure of the extension "Indexed Search" has been streamlined.


Impact
======

All language files are now located in directory Resources/Private/Language, the template files in Resources/Private/Templates.
Icons from pi/res directory have been moved to Resources/Public/Icons, images to Resources/Public/Images.


Affected Installations
======================

Installations that use EXT:indexed_search that depend on paths that have been moved.


Migration
=========

Make sure your configuration matches with new directory structure.


.. index:: ext:indexed_search
