.. include:: /Includes.rst.txt

================================================
Feature: #78760 - Resizable Navigation Component
================================================

See :issue:`78760`

Description
===========

The Navigation Component in TYPO3's backend, which shows e.g.
the Page Tree or the folder tree (within the file list module),
can be resized via Drag&Drop or via a button, which is layered
within the Navigation Component itself. A similar functionality
was previously put in the top bar on the left, for mobile devices,
but was removed in favor of this new solution.

The size of the navigation component is now stored in the users'
"uc" configuration to be persistent during various logins and
kept for multiple sessions.


Impact
======

TYPO3 now allows to not just resize the pagetree component,
but any navigation component (just like iframes).

When the component is collapsed, an icon is shown to indicate that
the navigation can be re-opened.

This makes it easier for editors to have a distraction-free
management interface when needed.

.. index:: Backend, ext:backend
