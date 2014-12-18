==============================================================
Deprecation: #63974 - Deprecate CSS compressor callback method
==============================================================

Description
===========

The callback method ``compressCssPregCallback`` as defined in
EXT:core/Classes/Resource/ResourceCompressor.php gets deprecated due to the overhauled regular expressions.

Impact
======

Usage of the mentioned method is deprecated.


Affected installations
======================

All installations or extensions using the ``compressCssPregCallback`` callback method.


Migration
=========

No migration possible for the callback method.