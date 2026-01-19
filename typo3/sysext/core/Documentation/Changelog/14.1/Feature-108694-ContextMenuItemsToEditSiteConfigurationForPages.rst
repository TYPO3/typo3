..  include:: /Includes.rst.txt

..  _feature-108694-1737323400:

==========================================================================
Feature: #108694 - Context menu items to edit site configuration for pages
==========================================================================

See :issue:`108694`

Description
===========

Two new context menu items have been added for pages that are site roots:

*   **Edit Site**: Opens the site configuration editor for the site associated
    with the page.
*   **Edit Site Settings**: Opens the site settings editor for the site.

These items appear directly after the "Edit" item in the context menu and are
only visible to admin users on pages that have a site configuration.

Impact
======

Admin users can now quickly access the site configuration and site settings
directly from the context menu when right-clicking on a site root page. This
improves the workflow for managing sites without needing to navigate to the
Site Management module first.

..  index:: Backend, ext:backend
