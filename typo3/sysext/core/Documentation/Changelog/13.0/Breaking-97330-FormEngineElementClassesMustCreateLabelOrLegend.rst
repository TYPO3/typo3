.. include:: /Includes.rst.txt

.. _breaking-97330-1687870738:

=========================================================================
Breaking: #97330 - FormEngine element classes must create label or legend
=========================================================================

See :issue:`97330`

Description
===========

When editing records in the backend, the :php:`FormEngine` class structure located
within :file:`EXT:backend/Classes/Form/` handles the generation of the editing view.

A change has been applied related to the rendering of single field labels, which
is no longer done automatically by "container" classes: Single elements have to
create the label themselves.

Extension that add own elements to FormEngine must be adapted, otherwise the
element label is no longer rendered.


Impact
======

When the required changes are not applied to custom FormEngine element classes,
the value of the TCA "label" property is not rendered.


Affected installations
======================

Instances with custom FormEngine elements are affected. Custom elements need to be
registered to the FormEngine's :php:`NodeFactory`, candidates are found by looking at
the :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']` array (for instance
using the :guilabel:`System > Configuration` backend module provided by
EXT:lowlevel). Classes registered using the sub keys :php:`nodeRegistry` and
:php:`nodeResolver` may be affected. The extension scanner does not find
affected classes.


Migration
=========

Custom elements must take care of creating a :html:`<label>` or :html:`<legend>`
tag on their own: If the element creates an :html:`<input>`, or :html:`<select>` tag,
the :html:`<label>` should have a :html:`for` attribute that points to a field having
an :html:`id` attribute. This is important especially for accessibility. When no such
target element exists, a :html:`<legend>` embedded in a :html:`<fieldset>` can be used.
There are two helper methods in
:php:`\TYPO3\CMS\Backend\Form\Element\AbstractFormElement` to help with this:
:php:`renderLabel()` and :php:`wrapWithFieldsetAndLegend()`.

In practice, an element having an :html:`<input>`, or :html:`<select>` field should
essentially look like this:

.. code-block:: php

    $resultArray = $this->initializeResultArray();
    // Next line is only needed for extensions that need to keep TYPO3 v12 compatibility
    $resultArray['labelHasBeenHandled'] = true;
    $fieldId = StringUtility::getUniqueId('formengine-input-');
    $html = [];
    $html[] = $this->renderLabel($fieldId);
    $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
    $html[] =     '<div class="form-wizards-wrap">';
    $html[] =         '<div class="form-wizards-element">';
    $html[] =             '<div class="form-control-wrap">';
    $html[] =                 '<input class="form-control" id="' . htmlspecialchars($fieldId) . '" value="..." type="text">';
    $html[] =             '</div>';
    $html[] =         '</div>';
    $html[] =     '</div>';
    $html[] = '</div>';
    $resultArray['html'] = implode(LF, $html);
    return $resultArray;

The :php:`renderLabel()` is a helper method to generate a :html:`<label>` tag with a
:html:`for` attribute, and the same fieldId is used as :html:`id` attribute in the
:html:`<input>` field to connect :html:`<label>` and :html:`<input>` with each other.

If there is no such field, a :html:`<legend>` tag should be used:

.. code-block:: php

    $resultArray = $this->initializeResultArray();
    // Next line is only needed for extensions that need to keep TYPO3 v12 compatibility
    $resultArray['labelHasBeenHandled'] = true;
    $html = [];
    $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
    $html[] =     '<div class="form-wizards-wrap">';
    $html[] =         '<div class="form-wizards-element">';
    $html[] =             '<div class="form-control-wrap">';
    $html[] =                 Some custom element html
    $html[] =             '</div>';
    $html[] =         '</div>';
    $html[] =     '</div>';
    $html[] = '</div>';
    $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));
    return $resultArray;


.. index:: Backend, NotScanned, ext:backend
