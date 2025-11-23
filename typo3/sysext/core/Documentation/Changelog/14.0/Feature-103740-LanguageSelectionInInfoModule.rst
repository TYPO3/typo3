..  include:: /Includes.rst.txt

..  _feature-103740-1714673346:

=====================================================================================
Feature: #103740 - Language selection for backend module "Status - Pagetree Overview"
=====================================================================================

See :issue:`103740`

Description
===========

The backend module :guilabel:`Content > Status > Pagetree Overview` has been
enhanced with a language selection option.

..  note::
    The module :guilabel:`Content > Status` was called :guilabel:`Web > Info`
    before TYPO3 v14, see also
    `Feature: #107628 - Improved backend module naming and structure <https://docs.typo3.org/permalink/changelog:feature-107628-1729026000>`_.

This change makes it possible to switch the displayed page tree to the selected
language and adjust all labels, as well as edit and view links, accordingly.

The language selection dropdown is located next to the other filter options
(recursion depth, information type) and complements the
:guilabel:`Content > Status > Localization Overview` module by providing a
page- and record-focused view.

Impact
======

The :guilabel:`Content > Status` backend module is now more useful for sites with
multiple languages, offering a quick overview of information for the selected
page and its subpages in the chosen language.

..  index:: Backend, ext:info
