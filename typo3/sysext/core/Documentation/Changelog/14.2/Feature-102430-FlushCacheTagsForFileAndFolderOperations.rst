.. include:: /Includes.rst.txt

.. _feature-102430-1700581800:

==================================================================
Feature: #102430 - Flush cache tags for file and folder operations
==================================================================

See :issue:`102430`

Description
===========

This feature is guarded with toggle "frontend.cache.autoTagging" and
experimental for now: The core flushes cache tags for all kind of records
automatically when they are created, changed or deleted. For files and
folders this is not the case. This feature adds cache tag handling for file
or folder operations when they are created, changed or deleted. Also the
file metadata changes are handled correctly now. This leads to a better
editor experience when the cache tags are used correctly.

Impact
======

Integrators and extension developers can now add :php:`sys_file_${uid}` and
:php:`sys_file_metadata_${uid}` as cache tags and they are flushed correctly
from the TYPO3 core when an editor interacts with them in the filelist module.

.. index:: FAL, PHP-API, ext:core
