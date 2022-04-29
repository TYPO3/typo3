.. include:: /Includes.rst.txt


.. _prototypes.<prototypeidentifier>.validatorsdefinition.stringlength:

==============
[StringLength]
==============


.. _prototypes.<prototypeidentifier>.validatorsdefinition.stringlength-validationerrorcodes:

Validation error codes
======================

- Error code: `1238110957`
- Error message: `The given object could not be converted to a string.`

- Error code: `1269883975`
- Error message: `The given value was not a valid string.`

- Error code: `1428504122`
- Error message: `The length of the given string was not between %s and %s characters.`

- Error code: `1238108068`
- Error message: `The length of the given string is less than %s characters.`

- Error code: `1238108069`
- Error message: `The length of the given string exceeded %s characters.`


.. _prototypes.<prototypeidentifier>.validatorsdefinition.stringlength-properties:

Properties
==========


.. _prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.implementationClassName

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

         StringLength:
           implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.options.minimum:

options.minimum
---------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.options.minimum

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      Minimum length for a valid string.


.. _prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.options.maximum:

options.maximum
---------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.options.maximum

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      Maximum length for a valid string.


.. _prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.formeditor.iconidentifier:

formEditor.iconIdentifier
-------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.formEditor.iconIdentifier

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

         StringLength:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.StringLength.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.formeditor.label:

formEditor.label
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.formEditor.label

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

         StringLength:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.StringLength.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.formeditor.predefineddefaults:

formEditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.formEditor.predefinedDefaults

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

         StringLength:
           formEditor:
             predefinedDefaults:
               options:
                 minimum: ''
                 maximum: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst
