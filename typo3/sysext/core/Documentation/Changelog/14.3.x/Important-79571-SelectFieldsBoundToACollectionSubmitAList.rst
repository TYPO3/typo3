..  include:: /Includes.rst.txt

..  _important-79571-1792595636:

=====================================================================
Important: #79571 - Select fields bound to a collection submit a list
=====================================================================

See :issue:`79571`

Description
===========

A :html:`<f:form.select>` without :html:`multiple` that is bound with
:html:`property` to a property whose current value is an array or an
:php:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage` now appends :html:`[]` to
its :html:`name` attribute, as :html:`<f:form.checkbox>` already does for such
a value.

Before, the field submitted a single uid. Extbase mapped it to an empty
:php:`ObjectStorage`, so the selection was lost and persisting the object
removed all relations it had. Now the selected value is submitted as a list
with one entry and mapped into the collection.

The value is read from the form object when the field is rendered. A select
in a form without an object, or bound to a property that is :php:`null`,
still renders a single name. Initialize such a property with an empty
:php:`ObjectStorage`, as Extbase models usually do in their constructor.

Selects bound to any other value and selects with :html:`multiple` render as
before. Code that addresses a select bound to a collection by its exact
:html:`name` attribute, for example in JavaScript, has to add the :html:`[]`.

..  index:: Fluid, Frontend, ext:fluid
