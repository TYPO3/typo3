.. include:: /Includes.rst.txt

.. _adjust-settings-of-widget:

=====================================
Adjust settings of registered widgets
=====================================

Each widget is registered with an identifier, and all :file:`Services.*` files are merged.
Therefore it is possible to override widgets.
In order to override, the extension which should override has to be loaded after the extension that registered the widget.

Concrete options depend on the widget to configure.
Each widget should provide an documentation covering all possible options and their meaning.
For delivered widgets by ext:dashboard see :ref:`widgets`.

In case an widget defined by ext:dashboard should be adjusted,
the extension has to define a dependency to ext:dashboard.

Afterwards the widget can be registered again, with different options. See
:ref:`register-new-widget` to get an in depth example of how to register an widget.

Why not adjust specific settings?
---------------------------------

There is no documented way to adjust specific settings,
as this would result in a situation where multiple extensions are loaded in different order
changing settings of widgets.
That would lead to an complex system.
