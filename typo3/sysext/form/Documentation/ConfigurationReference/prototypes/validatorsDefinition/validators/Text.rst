.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.validatorsdefinition.text:

======
[Text]
======


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.text-validationerrorcodes:

validation error codes
======================

- 1221565786


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.text-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.text.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.Text.implementationClassName

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

         Text:
           implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\TextValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-frontendrendering-codecomponents-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.text.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.Text.formEditor.iconIdentifier

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

         Text:
           formEditor:
             iconIdentifier: t3-form-icon-validator
             label: formEditor.elements.TextMixin.editor.validators.Text.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.text.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.Text.formEditor.label

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

         Text:
           formEditor:
             iconIdentifier: t3-form-icon-validator
             label: formEditor.elements.TextMixin.editor.validators.Text.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst
