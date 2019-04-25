.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.datetime:

==========
[DateTime]
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.datetime-validationerrorcodes:

validation error codes
======================

- 1238087674


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.datetime-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.datetime.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.DateTime.implementationClassName

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

         DateTime:
           implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\DateTimeValidator
 
:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.datetime.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.DateTime.formEditor.iconIdentifier

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

         DateTime:
           formEditor:
             iconIdentifier: t3-form-icon-validator
             label: formEditor.elements.DatePicker.validators.DateTime.editor.header.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.datetime.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.DateTime.formEditor.label

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

         DateTime:
           formEditor:
             iconIdentifier: t3-form-icon-validator
             label: formEditor.elements.DatePicker.validators.DateTime.editor.header.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst
