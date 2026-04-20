.. include:: /Includes.rst.txt

.. _feature-102430-1700581800:

==================================================================
Feature: #102430 - Flush cache tags for file and folder operations
==================================================================

See :issue:`102430`

Description
===========

This feature is guarded by the `frontend.cache.autoTagging` feature toggle and
is currently experimental. The core flushes cache tags automatically for all
kinds of records when they are created, changed, or deleted. This is not the
case for files and folders. This feature adds cache tag handling for file and
folder operations when they are created, changed, or deleted. File metadata
changes are now handled correctly as well. This will lead to a better editor
experience if cache tags are used correctly.

Impact
======

Integrators and extension developers can now add `sys_file_${uid}` and
`sys_file_metadata_${uid}` as cache tags, and they are flushed correctly by
the TYPO3 core when an editor interacts with them in the :guilabel:`Media` module.

.. index:: FAL, PHP-API, ext:core
