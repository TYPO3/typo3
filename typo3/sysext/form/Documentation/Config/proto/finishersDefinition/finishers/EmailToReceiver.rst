.. include:: ../../../../Includes.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.emailtoreceiver:

=================
[EmailToReceiver]
=================

.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinitionemailtoreceiver-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.implementationClassName

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

         EmailToReceiver:
           implementationClassName: TYPO3\CMS\Form\Domain\Finishers\EmailFinisher

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-frontendrendering-codecomponents-customfinisherimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.subject:

options.subject
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.subject

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      Subject of the email.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.recipientaddress:

options.recipientAddress
------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.recipientAddress

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Email address of the recipient (To).


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.recipientname:

options.recipientName
---------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.recipientName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Human-readable name of the recipient.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.senderaddress:

options.senderAddress
---------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.senderAddress

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Email address of the sender/ visitor (From).


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.sendername:

options.senderName
------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.senderName

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Human-readable name of the sender.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.replytoaddress:

options.replyToAddress
----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.replyToAddress

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Email address of to be used as reply-to email (use multiple addresses with an array).

.. note::

   For the moment, the ``form editor`` cannot deal with multiple reply-to addresses (use multiple addresses with an array)


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.carboncopyaddress:

options.carbonCopyAddress
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.carbonCopyAddress

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Email address of the copy recipient (use multiple addresses with an array)

.. note::

   For the moment, the ``form editor`` cannot deal with multiple copy recipient addresses (use multiple addresses with an array)


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.blindcarboncopyaddress:

options.blindCarbonCopyAddress
------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.blindCarbonCopyAddress

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Email address of the blind copy recipient (use multiple addresses with an array)

.. note::

   For the moment, the ``form editor`` cannot deal with multiple blind copy recipient addresses (use multiple addresses with an array)


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.format:

options.format
--------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.format

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      html

:aspect:`Possible values`
      html/ plaintext

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      The format of the email. By default mails are sent as HTML.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.attachuploads:

options.attachUploads
---------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.attachUploads

:aspect:`Data type`
      bool

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      true

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      If set, all uploaded items are attached to the email.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.translation.language:

options.translation.language
----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.translation.language

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      If not set, the finisher options are translated depending on the current frontend language (if translations exists).
      This option allows you to force translations for a given sys_language isocode, e.g 'dk' or 'de'.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.translation.translationfile:

options.translation.translationFile
-----------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.translation.translationFile

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-frontendrendering-codecomponents-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      If set, this translation file(s) will be used for finisher option translations.
      If not set, the translation file(s) from the 'Form' element will be used.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.templatepathandfilename:

options.templatePathAndFilename
-------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.templatePathAndFilename

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

         EmailToReceiver:
           options:
             templatePathAndFilename: 'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/{@format}.html'

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`

:aspect:`Description`
      Template path and filename for the mail body.
      The placeholder {\@format} will be replaced with the value from option ``format``.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.partialrootpaths:

options.partialRootPaths
------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.partialRootPaths

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`

:aspect:`Description`
      Fluid layout paths.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.layoutrootpaths:

options.layoutRootPaths
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.layoutRootPaths

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`

:aspect:`Description`
      Fluid partial paths.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.options.variables:

options.variables
-----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.options.variables

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`

:aspect:`Description`
      Associative array of variables which are available inside the Fluid template.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.formEditor.iconIdentifier

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

         EmailToReceiver:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.EmailToReceiver.editor.header.label
             predefinedDefaults:
               options:
                 subject: ''
                 recipientAddress: ''
                 recipientName: ''
                 senderAddress: ''
                 senderName: ''
                 replyToAddress: ''
                 carbonCopyAddress: ''
                 blindCarbonCopyAddress: ''
                 format: html
                 attachUploads: true
                 translation:
                   language: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.formEditor.label

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

         EmailToReceiver:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.EmailToReceiver.editor.header.label
             predefinedDefaults:
               options:
                 subject: ''
                 recipientAddress: ''
                 recipientName: ''
                 senderAddress: ''
                 senderName: ''
                 replyToAddress: ''
                 carbonCopyAddress: ''
                 blindCarbonCopyAddress: ''
                 format: html
                 attachUploads: true
                 translation:
                   language: ''

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.formEditor.predefinedDefaults

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

         EmailToReceiver:
           formEditor:
             iconIdentifier: t3-form-icon-finisher
             label: formEditor.elements.Form.finisher.EmailToReceiver.editor.header.label
             predefinedDefaults:
               options:
                 subject: ''
                 recipientAddress: ''
                 recipientName: ''
                 senderAddress: ''
                 senderName: ''
                 replyToAddress: ''
                 carbonCopyAddress: ''
                 blindCarbonCopyAddress: ''
                 format: html
                 attachUploads: true
                 translation:
                   language: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.formengine.label:

FormEngine.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.FormEngine.label

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (plugin)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         EmailToReceiver:
           FormEngine:
             label: tt_content.finishersDefinition.EmailToReceiver.label

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      .. include:: ../properties/formEngine/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtoreceiver.formengine.elements:

FormEngine.elements
-------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToReceiver.FormEngine.elements

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (plugin)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 4-

         EmailToReceiver:
           FormEngine:
             label: tt_content.finishersDefinition.EmailToReceiver.label
             elements:
               subject:
                 label: tt_content.finishersDefinition.EmailToReceiver.subject.label
                 config:
                   type: input
               recipientAddress:
                 label: tt_content.finishersDefinition.EmailToReceiver.recipientAddress.label
                 config:
                   type: input
                   eval: required
               recipientName:
                 label: tt_content.finishersDefinition.EmailToReceiver.recipientName.label
                 config:
                   type: input
               senderAddress:
                 label: tt_content.finishersDefinition.EmailToReceiver.senderAddress.label
                 config:
                   type: input
                   eval: required
               senderName:
                 label: tt_content.finishersDefinition.EmailToReceiver.senderName.label
                 config:
                   type: input
               replyToAddress:
                 label: tt_content.finishersDefinition.EmailToReceiver.replyToAddress.label
                 config:
                   type: input
               carbonCopyAddress:
                 label: tt_content.finishersDefinition.EmailToReceiver.carbonCopyAddress.label
                 config:
                   type: input
               blindCarbonCopyAddress:
                 label: tt_content.finishersDefinition.EmailToReceiver.blindCarbonCopyAddress.label
                 config:
                   type: input
               format:
                 label: tt_content.finishersDefinition.EmailToReceiver.format.label
                 config:
                   type: select
                   renderType: selectSingle
                   minitems: 1
                   maxitems: 1
                   size: 1
                   items:
                     10:
                       - tt_content.finishersDefinition.EmailToSender.format.1
                       - html
                     20:
                       - tt_content.finishersDefinition.EmailToSender.format.2
                       - plaintext
               translation:
                 language:
                   label: tt_content.finishersDefinition.EmailToReceiver.language.label
                   config:
                     type: select
                     renderType: selectSingle
                     minitems: 1
                     maxitems: 1
                     size: 1
                     items:
                       10:
                         - tt_content.finishersDefinition.EmailToReceiver.language.1
                         - default

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      .. include:: ../properties/formEngine/elements.rst
