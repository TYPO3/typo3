.. include:: /Includes.rst.txt

===================================================================================
Feature: #93857 - Resizable navigation component for all element / record selectors
===================================================================================

See :issue:`93857`

Description
===========

The newly introduced possibility to resize and/or collapse the
navigation frame (e.g. Page Tree) in the main backend has been additionally
added to all Element Browser / Record Selectors, and Link Picker selections.

All modal areas with a Page Tree or File-based Folder Tree now
contain the same feature-set of collapsing / resizing, except
that the width is not installation-wide but is kept for the
main navigation area (initially 300 pixels) in a different place (set in the
backend user's :sql:`uc` :php:`navigation.width` property) than for the element browsers
modal areas (initially 250 pixels, set in the backend user's :sql:`uc`
:php:`selector.navigation.width` property).

A custom Lit-based web component is added, which is now re-used
in various places, and uses the same markup in all contexts.


Impact
======

Any backend user is now able to resize / collapse the navigation
area in the Record Selectors / Element Browser shipped with TYPO3 Core.

.. index:: Backend, JavaScript, ext:recordlist
