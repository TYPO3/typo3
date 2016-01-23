.. include:: ../../../Includes.txt


.. _change-layout-individual-form:

===============================================
Change the layout for an individual FORM object
===============================================

It is also possible to override the layout setting of a particular object
within the form, like a checkbox. The layout function within an object only
accepts the markup, like the following one.

.. code-block:: typoscript

  tt_content.mailform.20 {
    10 = CHECKBOX
    10 {
      label = I want to receive the monthly newsletter by email.
      layout (
        <input />
        <label />
      )
    }
  }

The example shows how to switch the input field and the label, just for this
particular checkbox.

