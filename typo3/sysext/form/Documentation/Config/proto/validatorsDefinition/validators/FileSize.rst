.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.validatorsdefinition.filesize:

==========
[FileSize]
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.filesize-validationerrorcodes:

validation error codes
======================

- 1505303626
- 1505305752
- 1505305753

.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.filesize-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.filesize.implementationClassName:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.FileSize.implementationClassName

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

             FileSize:
               implementationClassName: TYPO3\CMS\Form\Mvc\Validation\FileSizeValidator

:aspect:`Good to know`
      - :ref:`"Custom validator implementations"<concepts-frontendrendering-codecomponents-customvalidatorimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.filesize.options.minimum:

options.minimum
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.FileSize.options.minimum

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The minimum filesize to accep. Use the format <size>B|K|M|G. For exmaple: 10M means 10 Megabytes.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.filesize.options.maximum:

options.maximum
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.FileSize.options.maximum

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for prototype 'standard')`
      undefined

:aspect:`Description`
      The maximum filesize to accep. Use the format <size>B|K|M|G. For exmaple: 10M means 10 Megabytes.


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.filesize.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.FileSize.formEditor.iconIdentifier

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

             FileSize:
               formEditor:
                 iconIdentifier: t3-form-icon-validator
                 label: formEditor.elements.FileUploadMixin.validators.FileSize.editor.header.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.filesize.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.FileSize.formEditor.label

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

             FileSize:
               formEditor:
                 iconIdentifier: t3-form-icon-validator
                 label: formEditor.elements.FileUploadMixin.validators.FileSize.editor.header.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeidentifier>.validatorsdefinition.filesize.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.validatorsDefinition.FileSize.formEditor.predefinedDefaults

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

             FileSize:
               formEditor:
                 predefinedDefaults:
                   options:
                     minimum: '0B'
                     maximum: '10M'

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst
