.. include:: /Includes.rst.txt

.. _feature-100206-1679299435:

======================================================================
Feature: #100206 - Enable list/tile view for resources in link browser
======================================================================

See :issue:`100206`

Description
===========

With this change, we are rolling out the universal file-list
rendering for files and folders to the link browser. The link
browser implementation for files and folders is now part of the
filelist extension.

The link browser now allows the user to choose the display type
of resources to match the personal preference between list and
tile rendering.

When the user now edits a link for a folder, the entry point is
the parent folder of the selected element folder instead of
showing the contents of the selected resource. The user sees
the selected folder in the presented list, this behavior mimics
the handling of selected files.


Impact
======

The user is now presented a unified experience when handling
resources. The modern filelist rendering is now rolled out to
the link browser and now covers, the filelist module, element
browser and link browser.

.. index:: Backend, FAL, RTE, ext:filelist
