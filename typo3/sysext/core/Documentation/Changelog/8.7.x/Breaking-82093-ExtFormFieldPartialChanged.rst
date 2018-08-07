.. include:: ../../Includes.txt

=================================================================
Breaking: #82093 - EXT:form Partials/Field/Field.html has changed
=================================================================

See :issue:`82093`

Description
===========

To let the form field viewhelper attribute errorClass work, the partial
:file:`EXT:form/Resources/Private/Frontend/Partials/Field/Field.html` has been changed.

.. code-block:: html

   <f:form.validationResults for="{element.identifier}">

has been changed to

.. code-block:: html

   <f:form.validationResults for="{element.rootForm.identifier}.{element.identifier}">


Impact
======

Users who overwrite this partial by its own partial have to make adjustments.
Otherwise no `has-error` class will be rendered in case of form validation errors
into the parents :html:`<div class="form-group">` and the :html:`<span class="help-block">`
content will not be rendered.


Affected Installations
======================

All installations with overwritten partial :file:`EXT:form/Resources/Private/Frontend/Partials/Field/Field.html`


Migration
=========

Change the partial :file:`Field/Field.html` within your site package.

.. code-block:: html

   <f:form.validationResults for="{element.identifier}">

change to

.. code-block:: html

   <f:form.validationResults for="{element.rootForm.identifier}.{element.identifier}">


.. index:: Frontend, ext:form, NotScanned
