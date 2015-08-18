====================================================================
Breaking: #69168 - Non-tabbed view of Content Element Wizard removed
====================================================================

Description
===========

The "New Content Element Wizard" view to show possible content elements to create now has only the view
to show the elements in a tabbed view. The non-tabbed view variant has been removed without substitution.

The TSconfig option ``mod.wizards.newContentElement.renderMode`` has been removed.


Migration
=========

Remove the TSconfig option ``mod.wizards.newContentElement.renderMode`` from any configuration settings.