.. include:: /Includes.rst.txt


.. _prototypes.prototypeIdentifier.validatorsdefinition.daterange:

===========
[DateRange]
===========


.. _prototypes.prototypeIdentifier.validatorsdefinition.daterange-validationerrorcodes:

Validation error codes
======================

-   Error code: `1521293685`
-   Error message: `You must enter an instance of \DateTime.`

-   Error code: `1521293686`
-   Error message: `You must select a date before %s.`

-   Error code: `1521293687`
-   Error message: `You must select a date after %s.`

-   Error code: `1748345955`
-   Error message: `This field cannot be validated due to a configuration
    error. Please contact the website owner.`


.. _prototypes.prototypeIdentifier.validatorsdefinition.daterange-properties:

Properties
==========


.. _prototypes.prototypeIdentifier.validatorsdefinition.daterange.implementationClassName:

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

         DateRange:
           implementationClassName: TYPO3\CMS\Form\Mvc\Validation\DateRangeValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst.txt


.. _prototypes.prototypeIdentifier.validatorsdefinition.daterange.options.format:

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


.. _prototypes.prototypeIdentifier.validatorsdefinition.daterange.options.minimum:

options.minimum
---------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.options.minimum

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The minimum date. Use an absolute date in the format of
      `options.format` or a relative date expression.

      ..  versionadded:: 14.2
          :changelog: feature-106681-1740000000

          Relative date expressions.

      A relative date expression follows the syntax of the PHP function
      `strtotime()`. Examples are `today`, `tomorrow`, `-18 years`, and
      `+1 month`. The expression is evaluated when the form is validated.
      Absolute and relative dates can be mixed in one validator.

      The validator compares dates at midnight, so the time of day is ignored.
      If a value cannot be parsed, the validator adds the configuration error
      `1748345955` and logs the details.

      For a `Date` element, the form editor also writes the values into the
      HTML `min` and `max` attributes of the field. Relative expressions in
      these attributes are resolved to absolute `Y-m-d` dates when the form
      is rendered.

      ..  code-block:: yaml
          :caption: Example: The date of birth must be at least 18 years ago

          type: Date
          identifier: date-of-birth
          label: 'Date of birth'
          validators:
            -
              identifier: DateRange
              options:
                minimum: '1900-01-01'
                maximum: '-18 years'


.. _prototypes.prototypeIdentifier.validatorsdefinition.daterange.options.maximum:

options.maximum
---------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.validatorsDefinition.DateRange.options.maximum

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The maximum date. Use an absolute date in the format of
      `options.format` or a relative date expression. The rules for relative
      date expressions are described in
      :ref:`options.minimum <prototypes.prototypeIdentifier.validatorsdefinition.daterange.options.minimum>`.

      ..  versionadded:: 14.2
          :changelog: feature-106681-1740000000

          Relative date expressions.


.. _prototypes.prototypeIdentifier.validatorsdefinition.daterange.formeditor.iconidentifier:

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
      .. include:: ../properties/iconIdentifier.rst.txt


.. _prototypes.prototypeIdentifier.validatorsdefinition.daterange.formeditor.label:

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
      .. include:: ../properties/label.rst.txt


.. _prototypes.prototypeIdentifier.validatorsdefinition.daterange.formeditor.predefineddefaults:

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
      .. include:: ../properties/predefinedDefaults.rst.txt
