.. include:: ../../Includes.txt

=====================================================================
Feature: #80126 maximum field length not set as attribute "maxlength"
=====================================================================

See :issue:`80126`
See :issue:`80128`

Description
===========

If a form element is set to be use the 'String length' server side validation through the form editor, the client side
validation properties ``minlength`` and ``maxlength`` will be rendered.

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

.. index:: Frontend, Backend, ext:form
