.. include:: /Includes.rst.txt

.. _feature-98348-1663235550:

=====================================================
Feature: #98348 - Live Search moved into modal window
=====================================================

See :issue:`98348`

Description
===========

The live search located at the top right side of the TYPO3 backend now uses a
modal window to render the search controls and the results.
As more space is now available, the amount of search results is increased to 50
records per search.

The modal may be opened via a keyboard shortcut by pressing the :kbd:`Cmd` + :kbd:`K`
keystroke on macOS or the :kbd:`Ctrl` + :kbd:`K` keystroke on Windows and Linux
systems.

Impact
======

Moving the search into a modal provides more possibilities for future
enhancements, e.g. dynamic loading of more results or filters.

.. index:: Backend, ext:backend
