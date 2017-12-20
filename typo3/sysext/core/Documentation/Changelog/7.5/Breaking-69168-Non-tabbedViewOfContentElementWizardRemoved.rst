
.. include:: ../../Includes.txt

====================================================================
Breaking: #69168 - Removed non-tabbed view of Content Element Wizard
====================================================================

See :issue:`69168`

Description
===========

The "New Content Element Wizard" view to show possible content elements to create now only shows the elements in a tabbed view.
The non-tabbed view variant has been removed without substitution.

The TSconfig option `mod.wizards.newContentElement.renderMode` has been removed.


Migration
=========

Remove the TSconfig option `mod.wizards.newContentElement.renderMode` from any configuration settings.


.. index:: TSConfig, Backend
