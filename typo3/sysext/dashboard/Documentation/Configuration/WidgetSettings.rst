.. include:: /Includes.rst.txt

.. _adjust-settings-of-widget:

=====================================
Adjust settings of registered widgets
=====================================

Each widget is registered with an identifier, and all :file:`Services.*` files are merged.
Therefore it is possible to override widgets.
In order to override, the extension which should override has to be loaded after the extension that registered the widget.

Concrete options depend on the widget to configure.
Each widget should provide documentation covering all possible options and their meaning.
For delivered widgets by EXT:dashboard see :ref:`widgets`.

In case a widget defined by EXT:dashboard should be adjusted,
the extension has to define a dependency to EXT:dashboard.

Afterwards the widget can be registered again, with different options. See
:ref:`register-new-widget` to get an in depth example of how to register a widget.

Why not adjust specific settings?
---------------------------------

There is no documented way to adjust specific settings,
as this would result in a situation where multiple extensions are loaded in different order
changing settings of widgets.
That would lead to a complex system.
