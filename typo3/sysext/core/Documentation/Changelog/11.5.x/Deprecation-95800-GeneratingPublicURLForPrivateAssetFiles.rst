.. include:: /Includes.rst.txt

=============================================================================
Deprecation: #95800 - Deprecate generating public URL for private asset files
=============================================================================

See :issue:`95800`

Description
===========

Since TYPO3 6, extensions have been restructured to have public asset files in Resources/Public folder only.

Unfortunately having public assets in extensions located in other folders never was deprecated. This is now done.


Impact
======

Public assets of extensions (files that should be delivered by the web server) MUST be located in Resources/Public folder of the extension, otherwise a deprecation message is now emitted once a URL to such asset is resolved.


Affected Installations
======================

Installations having extensions activated, that have public asset files in other locations than Resources/Public.


Migration
=========

Extension authors should move all public assets to Resources/Public folder

.. index:: PHP-API, NotScanned, ext:core
