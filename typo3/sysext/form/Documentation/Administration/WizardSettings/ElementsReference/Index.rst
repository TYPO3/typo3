.. include:: ../../../Includes.txt


.. _wizard-settings-elements:

==================
Elements reference
==================

Overrule the default settings of the :ref:`option <wizard-settings-defaults-options-tab>`
tab for specific element types.

In the left "settings" part there is a tab called "Options". The contents
of this tab will adapt itself to the selected element type in the form.
If no elements configuration exists, the default settings will be used.


.. _overriding-element-settings:

Overriding element settings
===========================

It is possible to override the default option tab settings for each
element individually. This is done by using the same configuration as
in :ts:`mod.wizards.form.defaults.tabs.options`, but using this
configuration in :ts:`mod.wizards.form.elements.[elementName]`.

The example below will hide all the accordions within the option tab for
a text field (TEXTLINE element), except the filters:

.. code-block:: typoscript

   mod.wizards.form.elements {
     textline {
       showAccordions = filters
     }
   }

By using this setting you can show or hide accordions, attributes,
validation rules or filters, for each and every individual element.

