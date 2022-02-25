.. include:: /Includes.rst.txt


.. _typo3.cms.form.prototypes.validatorsdefinition.emailaddress:

==============
[EmailAddress]
==============


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.emailaddress-validationerrorcodes:

Validation error codes
======================

- Error code: `1221559976`
- Error message: `The given subject was not a valid email address.`


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.emailaddress-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.emailaddress.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.EmailAddress.implementationClassName

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

         EmailAddress:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.emailaddress.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.EmailAddress.formEditor.iconIdentifier

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

         EmailAddress:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.emailaddress.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.EmailAddress.formEditor.label

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

         EmailAddress:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.TextMixin.editor.validators.EmailAddress.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst
