.. include:: /Includes.rst.txt


.. _prototypes.<prototypeidentifier>.finishersdefinition.flashmessage:

==============
[FlashMessage]
==============

.. _prototypes.<prototypeidentifier>.finishersdefinitionflashmessage-properties:

Properties
==========


.. _prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.implementationClassName

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
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.messagebody:

options.messageBody
-------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.messageBody

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      The flash message body TEXT.


.. _prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.messagetitle:

options.messageTitle
--------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.messageTitle

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      The flash message title.


.. _prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.messagearguments:

options.messageArguments
------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.messageArguments

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      The flash message arguments, if needed.


.. _prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.messagecode:

options.messageCode
-------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.messageCode

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      null

:aspect:`Good to know`
      - :ref:`"FlashMessage finisher"<apireference-finisheroptions-flashmessagefinisher>`

:aspect:`Description`
      The flash message code, if needed.


.. _prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.severity:

options.severity
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.severity

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      :php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK` (0)

:aspect:`Good to know`
      - :ref:`"FlashMessage finisher"<apireference-finisheroptions-flashmessagefinisher>`

:aspect:`Description`
      The flash message severity code.
      See :t3src:`core/Classes/Type/ContextualFeedbackSeverity.php` cases for the codes.


.. _prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.options.translation.translationfiles:

options.translation.translationFiles
------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.options.translation.translationFiles

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      If set, this translation file(s) will be used for finisher option translations.
      If not set, the translation file(s) from the 'Form' element will be used.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.formEditor.iconIdentifier

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
             iconIdentifier: form-finisher
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


.. _prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.formEditor.label

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
             iconIdentifier: form-finisher
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


.. _prototypes.<prototypeIdentifier>.finishersdefinition.flashmessage.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.FlashMessage.formEditor.predefinedDefaults

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
             iconIdentifier: form-finisher
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
