.. include:: /Includes.rst.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.daterange:

===========
[DateRange]
===========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.daterange-validationerrorcodes:

Validation error codes
======================

- Error code: `1521293685`
- Error message: `You must enter an instance of \DateTime.`

- Error code: `1521293686`
- Error message: `You must select a date before %s.`

- Error code: `1521293687`
- Error message: `You must select a date after %s.`


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.daterange-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.daterange.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.implementationClassName

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

         Count:
           implementationClassName: TYPO3\CMS\Form\Mvc\Validation\DateRangeValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.daterange.options.minimum:

options.format
--------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.options.format

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:

         DateRange:
           options:
             format: Y-m-d

:aspect:`Description`
      The format of the date.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.daterange.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.formEditor.iconIdentifier

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

         DateRange:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.FormElement.validators.DateRange.editor.header.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.daterange.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.formEditor.label

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

         DateRange:
           formEditor:
             iconIdentifier: form-validator
             label: formEditor.elements.FormElement.validators.DateRange.editor.header.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.daterange.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.formEditor.predefinedDefaults

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

         DateRange:
           formEditor:
             predefinedDefaults:
               options:
                 minimum: ''
                 maximum: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst
