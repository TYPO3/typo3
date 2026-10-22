.. include:: /Includes.rst.txt

.. _breaking-73717-1789516800:

=====================================================================
Breaking: #73717 - ObjectStorage validation results keyed by position
=====================================================================

See :issue:`73717`

Description
===========

:php:`\TYPO3\CMS\Extbase\Validation\Validator\CollectionValidator` builds the
property path of a collection element from the key the collection yields while
iterating. For :php:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage` that key is
the :php:`spl_object_hash()` of the element, so validation results ended up
below a path such as :php:`toppings.00000000000002380000000000000000.title`.

Form field ViewHelpers address the same element by its position instead, as in
:html:`<f:form.textfield property="toppings.0.title" />`. Producer and consumer
therefore never agreed on the same path.

Elements of an :php-short:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage` are now keyed by their position, the same
way a plain array already behaved.

Impact
======

Validation results of :php-short:`\TYPO3\CMS\Extbase\Persistence\ObjectStorage` elements are now addressed by the
position of the element:

..  code-block:: php

    $result->forProperty('toppings.0.title')->getErrors();

Code and Fluid templates that relied on the object hash no longer find these
results and silently render nothing, because an unknown property path resolves
to an empty result object.

In exchange, the property path of a validation result now matches the property
path used by the form field ViewHelpers. The :html:`errorClass` of a form field
inside a collection therefore works without further ado, and
:html:`<f:form.validationResults>` can be addressed with the very same path
that names the field.

Affected installations
======================

Installations with Extbase forms that edit a collection of child objects and
render validation results per element. Such templates are recognizable by an
:html:`<f:for>` that passes its :html:`key` into a validation result lookup,
while the form fields below it use the iteration index:

..  code-block:: html

    <f:for each="{pizza.toppings}" as="topping" iteration="i" key="key">
        <f:form.validationResults for="pizza.toppings.{key}.title">
            <f:form.textfield property="toppings.{i.index}.title" />
        </f:form.validationResults>
    </f:for>

Installations that render validation results only for non-collection
properties are not affected.

Migration
=========

Use the iteration index for the validation result as well, which makes the
second addressing scheme obsolete:

..  code-block:: html

    <f:for each="{pizza.toppings}" as="topping" iteration="i">
        <f:form.validationResults for="pizza.toppings.{i.index}.title">
            <f:form.textfield property="toppings.{i.index}.title" />
        </f:form.validationResults>
    </f:for>

Where the lookup was only used to set an error class on the field, it can be
dropped entirely and left to the field itself:

..  code-block:: html

    <f:form.textfield property="toppings.{i.index}.title"
                      errorClass="has-error" />

.. index:: Fluid, PHP-API, NotScanned, ext:extbase
