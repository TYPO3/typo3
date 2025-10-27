..  include:: /Includes.rst.txt

..  _feature-17406-1762953087:

=====================================================================
Feature: #17406 - Enhance page type "Link" to fully support typolinks
=====================================================================

See :issue:`17406`

Description
===========

The former page type "External link" has been renamed to "Link" and now fully
supports all typolink capabilities.

It can be used, for example, to configure pages linking to:

*   external URLs
*   other pages
*   content elements or sections
*   files and folders
*   email addresses
*   telephone numbers
*   custom records, for example news records

Such pages can be displayed in menus and linked from the link wizard of type *Page*.

The upgrade wizard "Migrate links of pages of type link." automatically migrates
pages of the former type "External URL".

The page type "Shortcut" remains unchanged.

Impact
======

Editors and integrators can now use the updated page type "Link" to create
menus that contain any link type supported by typolink - including section
links for anchor-based navigation, email addresses, telephone numbers, or
custom record links (for example news records).

..  index:: ext:core
