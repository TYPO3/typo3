.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.validatorsdefinition.stringlength:

==============
[StringLength]
==============


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.stringlength-validationerrorcodes:

validation error codes
======================

- 1238110957
- 1269883975
- 1428504122
- 1238108068
- 1238108069


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.stringlength-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.implementationClassName

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

         StringLength:
           implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-validators-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.options.minimum:

options.minimum
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.options.minimum

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The minimum value to accep.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.options.maximum:

options.maximum
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.options.maximum

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The maximum value to accep.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.formEditor.iconIdentifier

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

         StringLength:
           formEditor:
             iconIdentifier: t3-form-icon-validator
             label: formEditor.elements.TextMixin.editor.validators.StringLength.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.formEditor.label

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

         StringLength:
           formEditor:
             iconIdentifier: t3-form-icon-validator
             label: formEditor.elements.TextMixin.editor.validators.StringLength.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.stringlength.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.StringLength.formEditor.predefinedDefaults

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

         StringLength:
           formEditor:
             predefinedDefaults:
               options:
                 minimum: ''
                 maximum: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst
