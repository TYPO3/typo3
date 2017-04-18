.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.validatorsdefinition.regularexpression:

===================
[RegularExpression]
===================


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.regularexpression-validationerrorcodes:

validation error codes
======================

- 1221565130


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.regularexpression-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.regularexpression.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.RegularExpression.implementationClassName

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

         RegularExpression:
           implementationClassName: TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-frontendrendering-codecomponents-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.regularexpression.options.regularExpression:

options.regularExpression
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.RegularExpression.options.regularExpression

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The regular expression to use for validation, used as given.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.regularexpression.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.RegularExpression.formEditor.iconIdentifier

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

         RegularExpression:
           formEditor:
             iconIdentifier: t3-form-icon-validator
             label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.regularexpression.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.RegularExpression.formEditor.label

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

         RegularExpression:
           formEditor:
             iconIdentifier: t3-form-icon-validator
             label: formEditor.elements.TextMixin.editor.validators.RegularExpression.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.regularexpression.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.RegularExpression.formEditor.predefinedDefaults

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

         RegularExpression:
           formEditor:
             predefinedDefaults:
               options:
                 regularExpression: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst
