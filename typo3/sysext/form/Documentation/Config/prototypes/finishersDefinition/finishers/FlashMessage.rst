.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.flashmessage:

==============
[FlashMessage]
==============

.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinitionflashmessage-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.implementationClassName

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

         FlashMessage:
           implementationClassName: TYPO3\CMS\Form\Domain\Finishers\FlashMessageFinisher

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-frontendrendering-codecomponents-customfinisherimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.messagebody:

options.messageBody
-------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.messageBody

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      null

:aspect:`Good to know`
      - :ref:`"FlashMessage finisher"<apireference-finisheroptions-flashmessagefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      The flash message body TEXT.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.messagetitle:

options.messageTitle
--------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.messageTitle

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Good to know`
      - :ref:`"FlashMessage finisher"<apireference-finisheroptions-flashmessagefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      The flash message title.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.messagearguments:

options.messageArguments
------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.messageArguments

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty array

:aspect:`Good to know`
      - :ref:`"FlashMessage finisher"<apireference-finisheroptions-flashmessagefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      The flash message arguments, if needed.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.messagecode:

options.messageCode
-------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.messageCode

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      null

:aspect:`Good to know`
      - :ref:`"FlashMessage finisher"<apireference-finisheroptions-flashmessagefinisher>`

:aspect:`Description`
      The flash message code, if needed.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.severity:

options.severity
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.severity

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      \TYPO3\CMS\Core\Messaging\AbstractMessage::OK (0)

:aspect:`Good to know`
      - :ref:`"FlashMessage finisher"<apireference-finisheroptions-flashmessagefinisher>`

:aspect:`Description`
      The flash message severity code.
      See \TYPO3\CMS\Core\Messaging\AbstractMessage constants for the codes.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.translation.translationfile:

options.translation.translationFile
-----------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.translation.translationFile

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"FlashMessage finisher"<apireference-finisheroptions-flashmessagefinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      If set, this translation file(s) will be used for finisher option translations.
      If not set, the translation file(s) from the 'Form' element will be used.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.formEditor.iconIdentifier

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

         FlashMessage:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.FlashMessage.editor.header.label
             predefinedDefaults:
               options:
                 messageBody: ''
                 messageTitle: ''
                 messageArguments: ''
                 messageCode: 0
                 severity: 0

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.formEditor.label

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

         FlashMessage:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.FlashMessage.editor.header.label
             predefinedDefaults:
               options:
                 messageBody: ''
                 messageTitle: ''
                 messageArguments: ''
                 messageCode: 0
                 severity: 0

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.formEditor.predefinedDefaults

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

         FlashMessage:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.FlashMessage.editor.header.label
             predefinedDefaults:
               options:
                 messageBody: ''
                 messageTitle: ''
                 messageArguments: ''
                 messageCode: 0
                 severity: 0

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst
