.. include:: /Includes.rst.txt

=======================================================================
Feature: #73176 - Filterable Trees in Record Selectors and Link Pickers
=======================================================================

See :issue:`73176`

Description
===========

TYPO3's Page Tree, which was reworked in TYPO3 v9 to be powered by SVG rendering,
and the Folder Tree in the File list module, which was also migrated to SVG
rendering in TYPO3 v11.1, have been integrated in the so-called Record
Selectors / File Selector ("Element Browser") and Link Pickers of TYPO3
backend.

The Record Selectors are used when e.g. choosing a :guilabel:`Target Page` for a
:guilabel:`Shortcut Page`, or selecting a :guilabel:`Storage Page` in a plugin.

The file selectors are used when choosing a file for a IRRE-based FAL-based
file reference.

Link Pickers are used when linking to a specific page, content element, file,
folder or custom records, such as news ("related news" in EXT:news).

All of these components within TYPO3 backend are now powered by SVG-based
tree renderings. In addition, this means they ship with the same feature-set
as the main navigation components, such as:

*  A filter within the items of a tree (for folder-based trees, this means,
   searching for file names within a folder is also possible)
*  A JSON-based AJAX-loading functionality for fetching just parts of the tree,
   keeping the same expand/collapse state as the main tree
*  The Page Tree's "Temporary Mount Point" feature has the same functionality
   and styling as the main navigation component
*  Resizing and expand/collapse of the tree area in all modals
*  Keyboard navigation within the tree components

The newly added tree components have the following addons:

*  When selecting (Record Selector) or linking to a specific page or folder, the
   item can be selected by a specific "link" action on the right hand of the tree.
*  Only showing specific mount points configurable via
   TSconfig :typoscript:`options.pageTree.altElementBrowserMountPoints`
*  The content area (for selecting a record on a specific page) is dynamically
   loaded via AJAX and loads much faster than before


Impact
======

All page- and folder-based trees are now completely streamlined all over TYPO3's
backend, in terms of UX and code / implementation.

The overall UX feels much faster for every editor of TYPO3, and the consistency
makes TYPO3 more intuitive with an improved search/filter and record selector.

.. index:: Backend, JavaScript, ext:backend
