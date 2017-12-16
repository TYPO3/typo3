
.. include:: ../../Includes.txt

==============================================================
Deprecation: #63974 - Deprecate CSS compressor callback method
==============================================================

See :issue:`63974`

Description
===========

The callback method `compressCssPregCallback()` as defined in
EXT:core/Classes/Resource/ResourceCompressor.php has been marked as deprecated due to the overhauled regular expressions.

Impact
======

Usage of the mentioned method is discouraged and will break as of CMS 8.


Affected installations
======================

All installations or extensions using the `compressCssPregCallback()` callback method.


Migration
=========

No migration possible for the callback method.
