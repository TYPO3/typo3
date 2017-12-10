.. include:: ../../Includes.txt

================================================================
Feature: #80186 - Add HTML5 elements and improve the form editor
================================================================

See :issue:`80186`
See :issue:`80130`
See :issue:`80128`
See :issue:`80127`
See :issue:`80125`
See :issue:`80126`

Description
===========

The form editor contains new selectable form elements
-----------------------------------------------------

* :html:`email` (HTML5)
* :html:`tel` (HTML5)
* :html:`url` (HTML5)
* :html:`number` (HTML5)

The server side 'TYPO3\CMS\Extbase\Validation\Validator\NumberValidator' validator can be used.
-----------------------------------------------------------------------------------------------

.. code-block:: yaml

    renderables:
      -
        type: <formElementType>
        ...
        validators:
          -
            identifier: Number

If a form element is set to be required through the form editor, the html client side validation property "required" will be rendered
-------------------------------------------------------------------------------------------------------------------------------------

Result:

.. code-block:: yaml

    renderables:
      -
        type: <formElementType>
        ...
        properties:
          fluidAdditionalAttributes:
            required: 'required'
            ...

If a form element is set to use the 'String length' server side validation through the form editor, the client side validation properties 'minlength' and 'maxlength' will be rendered
-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

Result:

.. code-block:: yaml

    renderables:
      -
        type: <formElementType>
        ...
        properties:
          fluidAdditionalAttributes:
            minlength: 2
            maxlength: 3
            ...
        validators:
          -
            identifier: StringLength
            options:
              minimum: 2
              maximum: 3

If a form element is set to use the 'Number range' server side validation through the form editor, the client side validation properties 'min' and 'max' will be rendered
----------------------------------------------------------------------------------------------------------------------------------------------------------------------------

.. code-block:: yaml

    renderables:
      -
        type: <formElementType>
        ...
        properties:
          fluidAdditionalAttributes:
            min: 2
            max: 3
            ...
        validators:
          -
            identifier: NumberRange
            options:
              minimum: 2
              maximum: 3

The form editor is able to set the 'pattern' client side validation property
----------------------------------------------------------------------------

.. code-block:: yaml

    renderables:
      -
        type: <formElementType>
        ...
        properties:
          fluidAdditionalAttributes:
            pattern: '^.*$'

The form editor validators select list will be removed if no validators are available
--------------------------------------------------------------------------------------


Impact
======

It is now possible to add HTML5 elements with its needs.

.. index:: Frontend, Backend, ext:form
