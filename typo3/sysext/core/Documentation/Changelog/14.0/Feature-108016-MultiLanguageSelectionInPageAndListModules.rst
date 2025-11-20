..  include:: /Includes.rst.txt

..  _feature-108016-1762701776:

====================================================================
Feature: #108016 - Multi-language selection in page and list modules
====================================================================

See :issue:`108016`

Description
===========

The :guilabel:`Content > Page` and :guilabel:`Content > List` modules now
support selecting multiple languages simultaneously for improved comparison
of translated records.

The language selection state is synchronized between both modules, providing
a consistent experience when switching between page and list views.

A fallback mechanism is in place for switching between view modes (Layout
and Language Comparison) as well as navigating to a page not available
in the current language selection.

.. note::

    The :guilabel:`Content > Preview` module has been migrated to the
    new shared language API, too. Therefore, a selected language is also
    kept when navigation to or from this module.


Impact
======

Users can now:

*   Select multiple specific languages to compare
*   Toggle individual languages on / off
*   Quickly select or deselect all available languages
*   See visual feedback with appropriate icons and counts

The language selection is persisted in module data and shared between the
page module and list module for the same page.

.. index:: Backend, ext:backend
