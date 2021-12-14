.. include:: /Includes.rst.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.confirmation:

==============
[Confirmation]
==============

.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinitionconfirmation-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.implementationClassName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2

         Confirmation:
           implementationClassName: TYPO3\CMS\Form\Domain\Finishers\ConfirmationFinisher

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.options.message:

options.message
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.options.message

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      The form has been submitted.

:aspect:`Good to know`
      - :ref:`"Confirmation finisher"<apireference-finisheroptions-confirmationfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      The text which is shown if the finisher is invoked.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.options.contentelementuid:

options.contentElementUid
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.options.contentElementUid

:aspect:`Data type`
      integer

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Description`
      The option "contentElementUid" can be used to render a content element.
      If contentElementUid is set, the option "message" will be ignored.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.options.typoscriptobjectpath:

options.typoscriptObjectPath
----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.options.typoscriptObjectPath

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      'lib.tx_form.contentElementRendering'

:aspect:`Description`
      The option "typoscriptObjectPath" can be used to render the content element (options.contentElementUid) through a typoscript lib.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.options.variables:

options.variables
-----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.options.variables

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Description`
      Variables which should be available within the template.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.options.templatename:

options.templateName
--------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.options.templateName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      'Confirmation'

:aspect:`Description`
      Define a custom template name which should be used.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.options.templaterootpaths:

options.templateRootPaths
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.options.templateRootPaths

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         Confirmation:
           options:
             templateRootPaths:
               10: 'EXT:form/Resources/Private/Frontend/Templates/Finishers/Confirmation/'

:aspect:`Description`
      Used to define several paths for templates, which will be tried in reversed order (the paths are searched from bottom to top).


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.options.translation.translationfiles:

options.translation.translationFiles
------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.options.translation.translationFiles

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Confirmation finisher"<apireference-finisheroptions-confirmationfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      If set, this translation file(s) will be used for finisher option translations.
      If not set, the translation file(s) from the 'Form' element will be used.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.formEditor.iconIdentifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         Confirmation:
           formEditor:
             iconIdentifier: form-finisher
             label: formEditor.elements.Form.finisher.Confirmation.editor.header.label
             predefinedDefaults:
               options:
                 message: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.formEditor.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 4

         Confirmation:
           formEditor:
             iconIdentifier: form-finisher
             label: formEditor.elements.Form.finisher.Confirmation.editor.header.label
             predefinedDefaults:
               options:
                 message: ''

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.confirmation.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.Confirmation.formEditor.predefinedDefaults

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 5-

         Confirmation:
           formEditor:
             iconIdentifier: form-finisher
             label: formEditor.elements.Form.finisher.Confirmation.editor.header.label
             predefinedDefaults:
               options:
                 message: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst

