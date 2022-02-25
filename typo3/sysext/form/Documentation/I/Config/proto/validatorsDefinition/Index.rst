.. include:: /Includes.rst.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition:

======================
[validatorsDefinition]
======================


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.*:

[validatorsDefinition]
----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         prototypes:
           <prototypeIdentifier>:
             validatorsDefinition:
               [...]

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      Array which defines the available serverside validators. Every key within this array is called the ``<validatoridentifier>``.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.<validatoridentifier>:

<validatorIdentifier>
---------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.<validatorIdentifier>

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      .. code-block:: yaml
         :linenos:

         prototypes:
           standard:
             NotEmpty:
               [...]
             DateTime:
               [...]
             Alphanumeric:
               [...]
             Text:
               [...]
             StringLength:
               [...]
             EmailAddress:
               [...]
             Integer:
               [...]
             Float:
               [...]
             NumberRange:
               [...]
             RegularExpression:
               [...]
             Count:
               [...]
             FileSize:
               [...]

:aspect:`Related options`
      - :ref:`"TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formElementsDefinition.\<formElementTypeIdentifier>.formEditor.propertyCollections.validators.[*].identifier"<typo3.cms.form.prototypes.\<prototypeIdentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.propertycollections.validators.*.identifier>`
      - :ref:`"[ValidatorsEditor] selectOptions.[*].value"<typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.selectoptions.*.value-validatorseditor>`
      - :ref:`"[RequiredValidatorEditor] validatorIdentifier"<typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.validatoridentifier-requiredvalidatoreditor>`

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      This array key identifies a validator. This identifier could be used to attach a validator to a form element.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.<validatoridentifier>-commonproperties:

Common <validatorIdentifier> properties
=======================================

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.validatorsdefinition.<validatoridentifier>.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.<validatorIdentifier>.implementationClassName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete validators configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.\<validatoridentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.validatorsdefinition.<validatoridentifier>.options:

options
-------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.<validatorIdentifier>.options

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend/ Backend (form editor)

:aspect:`Mandatory`
      Depends (see :ref:`concrete validators configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.\<validatoridentifier>-concreteconfigurations>`)

:aspect:`Default value`
      Depends (see :ref:`concrete validators configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.\<validatoridentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      Array with validator options.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.validatorsdefinition.<validatoridentifier>.formeditor:

formEditor
----------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.<validatorIdentifier>.formEditor

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Recommended

:aspect:`Default value`
      Depends (see :ref:`concrete validators configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.\<validatoridentifier>-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      Array with configurations for the ``form editor``


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.validatorsdefinition.<validatoridentifier>.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.<validatorIdentifier>.formEditor.iconIdentifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete validators configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.\<validatoridentifier>-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.validatorsdefinition.<validatoridentifier>.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.<validatorIdentifier>.formEditor.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      Depends (see :ref:`concrete validators configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.\<validatoridentifier>-concreteconfigurations>`)

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.validatorsdefinition.<validatoridentifier>.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.<validatorIdentifier>.formEditor.predefinedDefaults

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      Depends (see :ref:`concrete validators configuration <typo3.cms.form.prototypes.\<prototypeidentifier>.validatorsdefinition.\<validatoridentifier>-concreteconfigurations>`)

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: properties/predefinedDefaults.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.<validatoridentifier>-concreteconfigurations:

Concrete configurations
=======================

.. toctree::

    validators/Alphanumeric
    validators/Count
    validators/DateRange
    validators/DateTime
    validators/EmailAddress
    validators/FileSize
    validators/Float
    validators/Integer
    validators/NotEmpty
    validators/Number
    validators/NumberRange
    validators/RegularExpression
    validators/StringLength
    validators/Text
