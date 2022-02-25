.. include:: /Includes.rst.txt

.. _typo3.cms.form.prototypes.validatorsdefinition.numberrange:

=============
[NumberRange]
=============


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.numberrange-validationerrorcodes:

Validation error codes
======================

- Error code: `1221563685`
- Error message: `The given subject was not a valid number.`

- Error code: `1221561046`
- Error message: `The given subject was not in the valid range (%s - %s).`


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.numberrange-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.numberrange.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.NumberRange.implementationClassName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2

         NumberRange:
           implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\NumberRangeValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.numberrange.options.minimum:

options.minimum
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.NumberRange.options.minimum

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The minimum value to accept.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.numberrange.options.maximum:

options.maximum
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.NumberRange.options.maximum

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The maximum value to accept.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.numberrange.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.NumberRange.formEditor.iconIdentifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         NumberRange:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.NumberRange.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.numberrange.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.NumberRange.formEditor.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 4

         NumberRange:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.NumberRange.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.numberrange.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.NumberRange.formEditor.predefinedDefaults

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3-

         NumberRange:
           formEditor:
             predefinedDefaults:
               options:
                 minimum: ''
                 maximum: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst
