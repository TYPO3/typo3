.. include:: /Includes.rst.txt


.. _quickstartIntegrators:

===========================
Quick Start for Integrators
===========================

You are an integrator, your admin or you installed the form extension
and you want to get started quickly? Just follow these steps!

.. rst-class:: bignums-xxl

1. Include static TypoScript templates

   First, open the ``Template`` module in the backend and edit your root
   TypoScript template. Under the tab "Includes", ensure that Fluid Content
   Elements (fluid_styled_content) and Form (form) are among the selected
   items. Save the template.

2. Create a new form

   Go to the ``Forms`` module, and create a new form there. With the help of
   the form editor you can build appealing forms easily.

3. Move the form definition

   If you wish, you can :ref:`move the form definition to a dedicated
   extension<concepts-form-file-storages>`.

4. Provide a translation

   You can also provide a :ref:`translation<concepts-frontendrendering-translation>`
   of your form, if needed. This is done in an .xlf file which has to be
   registered in your YAML configuration.

5. Insert your form in a page

   The final step is inserting the form in the desired page(s).

   #. Open the page module in the backend.
   #. Select the desired page.
   #. Create a new content element of type "Form". You can find this one
      under the tab "Form Elements".
   #. Under the tab "Plugin", choose the desired form.
   #. If needed, you can select "Override finisher settings" under the
      "Plugin" tab. Save the content element.
   #. Repeat steps 2 to 5 until the form is inserted in every page requiring
      it.


You should now be able to view your form on the frontend. Enjoy!
