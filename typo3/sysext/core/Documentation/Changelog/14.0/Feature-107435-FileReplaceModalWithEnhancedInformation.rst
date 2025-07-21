.. include:: /Includes.rst.txt

.. _feature-107435-1758106735:

============================================================================
Feature: #107435 - File replace modal with enhanced file information
============================================================================

See :issue:`107435`

Description
===========

The file replacement functionality has been significantly improved to provide
a better user experience in the TYPO3 backend. Instead of opening the file
replacement interface in a new window, it now opens in a modal dialog,
maintaining the user's context and providing a more consistent experience
with other TYPO3 backend operations.

The modal displays thumbnails for supported file types, allowing
users to visually verify the current file before replacement.
Additionally, the following detailed information are shown:

* File type
* File size
* Creation date

Impact
======

This enhancement significantly improves the file management experience in
TYPO3 by providing a more modern, context-aware interface for file
replacement. The combination of modal-based interaction and file
information display creates a more efficient and user-friendly workflow
that aligns with modern web application patterns.

The changes particularly benefit content editors who frequently work with
files, providing them with the visual and metadata context needed to make
confident file replacement decisions without interrupting their content
creation workflow.

.. index:: Backend, UX, ext:core, ext:filelist
