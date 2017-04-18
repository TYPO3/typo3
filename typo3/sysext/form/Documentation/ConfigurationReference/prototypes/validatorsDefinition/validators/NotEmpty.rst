.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.notempty:

==========
[NotEmpty]
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.notempty-validationerrorcodes:

validation error codes
======================

- 1221560910
- 1221560718
- 1347992400
- 1347992453


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.notempty-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.notempty.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.NotEmpty.implementationClassName

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
      - :ref:`"Custom validator implementations"<concepts-frontendrendering-codecomponents-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.notempty.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.NotEmpty.formEditor.iconIdentifier

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
             iconIdentifier: t3-form-icon-validator
             label: formEditor.elements.FormElement.editor.requiredValidator.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.notempty.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.NotEmpty.formEditor.label

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
             iconIdentifier: t3-form-icon-validator
             label: formEditor.elements.FormElement.editor.requiredValidator.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst
