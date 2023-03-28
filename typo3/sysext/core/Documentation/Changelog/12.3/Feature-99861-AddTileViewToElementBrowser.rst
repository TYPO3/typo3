.. include:: /Includes.rst.txt

.. _feature-99861-1675757796:

==================================================
Feature: #99861 - Add tile view to element browser
==================================================

See :issue:`99861`

Description
===========

The file list is the default implementation for TYPO3 to navigate and
manage assets. This patch extends the usage of the file list to the
element browser, the build-in component to select the assets for file
fields and folder fields in the backend.

Impact
======

The rendering of files and folder now deliver a unified experience and
allow the user to use the tile view to select assets.

The search within the file browser now respects the selected folder and
searches all subfolders for the provided search term.

To have an even more reliable experience, the user will now always start
the selection process in the root folder of the default storage.

Resource tiles are now adapting to the surrounding container instead of
the viewport, to make better use of the available space.

The file list now holds all related code to the file and folder browser.

.. index:: Backend, FAL, ext:filelist
