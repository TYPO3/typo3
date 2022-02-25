.. include:: /Includes.rst.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.alphanumeric:

==============
[Alphanumeric]
==============


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.alphanumeric-validationerrorcodes:

Validation error codes
======================

- Error code: `1221551320`
- Error message: `The given subject was not a valid alphanumeric string.`


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.alphanumeric-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.alphanumeric.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.Alphanumeric.implementationClassName

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

         Alphanumeric:
           implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.alphanumeric.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.Alphanumeric.formEditor.iconIdentifier

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

         Alphanumeric:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.Alphanumeric.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.alphanumeric.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.Alphanumeric.formEditor.label

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

         Alphanumeric:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.Alphanumeric.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst
