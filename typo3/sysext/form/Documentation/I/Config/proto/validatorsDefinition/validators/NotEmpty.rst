.. include:: /Includes.rst.txt


.. _prototypes.<prototypeidentifier>.validatorsdefinition.notempty:

==========
[NotEmpty]
==========


.. _prototypes.<prototypeidentifier>.validatorsdefinition.notempty-validationerrorcodes:

Validation error codes
======================

- Error code: `1221560910`
- Error message: `The given subject was NULL.`

- Error code: `1221560718`
- Error message: `The given subject was empty.`

- Error code: `1347992400`
- Error message: `The given subject was empty.`

- Error code: `1347992453`
- Error message: `The given subject was empty.`


.. _prototypes.<prototypeidentifier>.validatorsdefinition.notempty-properties:

Properties
==========


.. _prototypes.<prototypeidentifier>.validatorsdefinition.notempty.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.NotEmpty.implementationClassName

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

         NotEmpty:
           implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _prototypes.<prototypeidentifier>.validatorsdefinition.notempty.formeditor.iconidentifier:

formEditor.iconIdentifier
-------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.NotEmpty.formEditor.iconIdentifier

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

         NotEmpty:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.FormElement.editor.requiredValidator.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _prototypes.<prototypeidentifier>.validatorsdefinition.notempty.formeditor.label:

formEditor.label
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.NotEmpty.formEditor.label

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

         NotEmpty:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.FormElement.editor.requiredValidator.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst
