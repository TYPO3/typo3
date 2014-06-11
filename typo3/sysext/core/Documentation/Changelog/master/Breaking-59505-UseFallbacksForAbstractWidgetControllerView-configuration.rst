================================================================================
Breaking: #59505 - Use fallbacks for AbstractWidgetController view-configuration
================================================================================

Description
===========

The ``AbstractWidgetController`` is now capable of view fallbacks. This it is using the existing functionality of Extbase controllers.


Impact
======

Paths to templates, layouts and partials specified in the old syntax do not work anymore.

Old syntax:

.. code-block:: typoscript

	plugin.tx_ext.settings.view.widget.widgetName.templateRootPath = some/path/Template.html


Affected Installations
======================

Any installation using third party extensions including wizards


Migration
=========

Specifying paths for layouts, templates and partials must now use the array syntax.

New syntax:

.. code-block:: typoscript

	plugin.tx_ext.settings.view.widget.widgetName.templateRootPaths.10 = some/path/Template.html

