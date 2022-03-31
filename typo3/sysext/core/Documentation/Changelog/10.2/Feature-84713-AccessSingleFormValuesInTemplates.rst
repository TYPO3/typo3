.. include:: /Includes.rst.txt

========================================================
Feature: #84713 - Access single values in form templates
========================================================

See :issue:`84713`

Description
===========

It is now possible to access single form values in templates of the "form" extension. For this a new :php:`RenderFormValueViewHelper` has been added which complements the existing :php:`RenderAllFormValuesViewHelper`.

The :php:`RenderFormValueViewHelper` accepts a single form element and renders it exactly like the :php:`RenderAllFormValuesViewHelper` used to do within its internal traversal of renderable elements:

To make it possible to access single form elements, a new method :php:`FormDefinition::getElements()` has been added. This method returns an array containing all elements in the form with their identifiers as keys.

Viewhelper usage in mail templates:

.. code-block:: html

   <p>The following message was just sent by <b><formvh:renderFormValue renderable="{form.formDefinition.elements.name}" as="formValue">{formValue.processedValue}</formvh:renderFormValue><b>:</p>

   <blockquote>
      <formvh:renderFormValue renderable="{form.formDefinition.elements.message}" as="formValue">
         {formValue.processedValue}
      </formvh:renderFormValue>
   </blockquote>

See which elements are accessible in mail templates:

.. code-block:: html

   <f:debug>{form.formDefinition.elements}</f:debug>

Viewhelper usage in the `SummaryPage` partial:

.. code-block:: html

   <p>The following message was just sent by <b><formvh:renderFormValue renderable="{page.rootForm.elements.name}" as="formValue">{formValue.processedValue}</formvh:renderFormValue><b>:</p>

   <blockquote>
      <formvh:renderFormValue renderable="{page.rootForm.elements.message}" as="formValue">
         {formValue.processedValue}
      </formvh:renderFormValue>
   </blockquote>

See which elements are accessible in the `SummaryPage` partial:

.. code-block:: html

   <f:debug>{page.rootForm.elements}</f:debug>

.. attention::
   The form elements are accessed differently depending on the kind of template.
   In mail templates `{form.formDefinition.elements}` is used,
   in the `SummaryPage` partial `{page.rootForm.elements}` is used.

Impact
======

Form values can now be placed freely in Fluid templates of the "form" extension instead of being bound to traverse all form values and skip rendering.

.. index:: Fluid, Frontend, ext:form
