.. include:: /Includes.rst.txt


.. _prototypes.prototypeIdentifier.validatorsdefinition.regularexpression:

===================
[RegularExpression]
===================


.. _prototypes.prototypeIdentifier.validatorsdefinition.regularexpression-validationerrorcodes:

Validation error codes
======================

- Error code: `1221565130`
- Error message: `The given subject did not match the pattern.`


.. _prototypes.prototypeIdentifier.validatorsdefinition.regularexpression-properties:

Properties
==========


.. _prototypes.prototypeIdentifier.validatorsdefinition.regularexpression.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.RegularExpression.implementationClassName

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

         RegularExpression:
           implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst.txt


.. _prototypes.prototypeIdentifier.validatorsdefinition.regularexpression.options.regularExpression:

options.regularExpression
-------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.RegularExpression.options.regularExpression

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The regular expression to use for validation, used as given.


.. _prototypes.prototypeIdentifier.validatorsdefinition.regularexpression.formeditor.iconidentifier:

formEditor.iconIdentifier
-------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.RegularExpression.formEditor.iconIdentifier

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

         RegularExpression:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst.txt


.. _prototypes.prototypeIdentifier.validatorsdefinition.regularexpression.formeditor.label:

formEditor.label
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.RegularExpression.formEditor.label

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

         RegularExpression:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst.txt


.. _prototypes.prototypeIdentifier.validatorsdefinition.regularexpression.formeditor.predefineddefaults:

formEditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.RegularExpression.formEditor.predefinedDefaults

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

         RegularExpression:
           formEditor:
             predefinedDefaults:
               options:
                 regularExpression: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst.txt
