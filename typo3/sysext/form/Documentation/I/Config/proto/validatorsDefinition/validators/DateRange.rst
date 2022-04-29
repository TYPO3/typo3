.. include:: /Includes.rst.txt


.. _prototypes.<prototypeidentifier>.validatorsdefinition.daterange:

===========
[DateRange]
===========


.. _prototypes.<prototypeidentifier>.validatorsdefinition.daterange-validationerrorcodes:

Validation error codes
======================

- Error code: `1521293685`
- Error message: `You must enter an instance of \DateTime.`

- Error code: `1521293686`
- Error message: `You must select a date before %s.`

- Error code: `1521293687`
- Error message: `You must select a date after %s.`


.. _prototypes.<prototypeidentifier>.validatorsdefinition.daterange-properties:

Properties
==========


.. _prototypes.<prototypeidentifier>.validatorsdefinition.daterange.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.implementationClassName

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


.. _prototypes.<prototypeidentifier>.validatorsdefinition.daterange.options.format:

options.format
--------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.options.format

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
      The format of the minimum and maximum option.


.. _prototypes.<prototypeidentifier>.validatorsdefinition.daterange.options.minimum:

options.minimum
---------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.options.minimum

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The minimum date formatted as Y-m-d.


.. _prototypes.<prototypeidentifier>.validatorsdefinition.daterange.options.maximum:

options.maximum
---------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.options.maximum

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The maximum date formatted as Y-m-d.


.. _prototypes.<prototypeidentifier>.validatorsdefinition.daterange.formeditor.iconidentifier:

formEditor.iconIdentifier
-------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.formEditor.iconIdentifier

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


.. _prototypes.<prototypeidentifier>.validatorsdefinition.daterange.formeditor.label:

formEditor.label
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.formEditor.label

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


.. _prototypes.<prototypeidentifier>.validatorsdefinition.daterange.formeditor.predefineddefaults:

formEditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.formEditor.predefinedDefaults

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
