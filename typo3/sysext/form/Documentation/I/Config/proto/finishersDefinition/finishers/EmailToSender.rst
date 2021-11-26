.. include:: /Includes.rst.txt


.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinition.emailtosender:

=================
[EmailToSender]
=================

.. _typo3.cms.form.prototypes.<prototypeidentifier>.finishersdefinitionemailtosender-properties:

Properties
==========


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.implementationClassName

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

         EmailToSender:
           implementationClassName: TYPO3\CMS\Form\Domain\Finishers\EmailFinisher

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.subject:

options.subject
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.subject

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      Subject of the email.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.recipients:

options.recipients
------------------

:aspect:`Option path`
    TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.recipients

:aspect:`Data type`
    array

:aspect:`Needed by`
    Frontend

:aspect:`Mandatory`
    Yes

:aspect:`Default value`
    undefined

:aspect:`Good to know`
    - :ref:`"Email finisher"<apireference-finisheroptions-emailfinisher>`
    - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
    Email addresses and names of the recipients (To).

    The form editor in the backend module provides a visual UI to enter an arbitrary
    amount of recipients.

    This option must contain a YAML hash with email addresses as keys and
    recipient names as values:

    .. code-block:: yaml

       recipients:
         first@example.org: First Recipient
         second@example.org: Second Recipient

.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.senderaddress:

options.senderAddress
---------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.senderAddress

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Email address of the sender/ visitor (From).


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.sendername:

options.senderName
------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.senderName

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Human-readable name of the sender.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.replytorecipients:

options.replyToRecipients
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.replyToRecipients

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Email addresses of to be used as reply-to emails.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.carboncopyrecipients:

options.carbonCopyRecipients
----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.carbonCopyRecipients

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Email addresses of the copy recipient.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.blindcarboncopyrecipients:

options.blindCarbonCopyRecipients
---------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.blindCarbonCopyRecipients

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Email address of the blind copy recipient.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.addhtmlpart:

options.addHtmlPart
-------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.addHtmlPart

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      If set, mails will contain a plaintext and HTML part, otherwise only a
      plaintext part. That way, it can be used to disable HTML and enforce
      plaintext-only mails.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.attachuploads:

options.attachUploads
---------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.attachUploads

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      If set, all uploaded items are attached to the email.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.title:

options.title
-------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.title

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      The title, being shown in the email. The templates are based onFluidEmail.
      The template renders the title field in the header section right above the
      email body. Do not confuse this field with the subject of the email.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.translation.language:

options.translation.language
----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.translation.language

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      If not set, the finisher options are translated depending on the current frontend language (if translations exists).
      This option allows you to force translations for a given sys_language isocode, e.g 'dk' or 'de'.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.translation.translationfiles:

options.translation.translationFiles
------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.translation.translationFiles

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
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      If set, this translation file(s) will be used for finisher option translations.
      If not set, the translation file(s) from the 'Form' element will be used.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.partialrootpaths:

options.partialRootPaths
------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.partialRootPaths

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


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.layoutrootpaths:

options.layoutRootPaths
-----------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.layoutRootPaths

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


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.options.variables:

options.variables
-----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.options.variables

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


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.formEditor.iconIdentifier

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

         EmailToSender:
           formEditor:
             iconIdentifier: form-finisher
             label: formEditor.elements.Form.finisher.EmailToSender.editor.header.label

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.formEditor.label

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

         EmailToSender:
           formEditor:
             iconIdentifier: form-finisher
             label: formEditor.elements.Form.finisher.EmailToSender.editor.header.label

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.formEditor.predefinedDefaults

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

         EmailToSender:
           formEditor:
             iconIdentifier: form-finisher
             label: formEditor.elements.Form.finisher.EmailToSender.editor.header.label
             predefinedDefaults:
               options:
                 subject: ''
                 recipients: {  }
                 senderAddress: ''
                 senderName: ''
                 replyToRecipients: {  }
                 carbonCopyRecipients: {  }
                 blindCarbonCopyRecipients: {  }
                 addHtmlPart: true
                 attachUploads: true
                 translation:
                   language: 'default'
                 title: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.formengine.label:

FormEngine.label
----------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.FormEngine.label

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

         EmailToSender:
           FormEngine:
             label: tt_content.finishersDefinition.EmailToSender.label

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      .. include:: ../properties/formEngine/label.rst


.. _typo3.cms.form.prototypes.<prototypeIdentifier>.finishersdefinition.emailtosender.formengine.elements:

FormEngine.elements
-------------------

:aspect:`Option path`
      TYPO3.CMS.Form.prototypes.<prototypeIdentifier>.finishersDefinition.EmailToSender.FormEngine.elements

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

         EmailToSender:
           FormEngine:
             label: tt_content.finishersDefinition.EmailToSender.label
             elements:
               subject:
                 label: tt_content.finishersDefinition.EmailToSender.subject.label
                 config:
                   type: input
                   eval: required
               recipients:
                 title: tt_content.finishersDefinition.EmailToSender.recipients.label
                 type: array
                 section: true
                 sectionItemKey: email
                 sectionItemValue: name
                 el:
                   _arrayContainer:
                     type: array
                     title: tt_content.finishersDefinition.EmailToSender.recipients.item.label
                     el:
                       email:
                         TCEforms:
                           label: tt_content.finishersDefinition.EmailToSender.recipients.email.label
                           config:
                             type: input
                             eval: 'required,email'
                       name:
                         TCEforms:
                           label: tt_content.finishersDefinition.EmailToSender.recipients.name.label
                           config:
                             type: input
               senderAddress:
                 label: tt_content.finishersDefinition.EmailToSender.senderAddress.label
                 config:
                   type: input
                   eval: required
               senderName:
                 label: tt_content.finishersDefinition.EmailToSender.senderName.label
                 config:
                   type: input
               replyToRecipients:
                 title: tt_content.finishersDefinition.EmailToSender.replyToRecipients.label
                 type: array
                 section: true
                 sectionItemKey: email
                 sectionItemValue: name
                 el:
                   _arrayContainer:
                     type: array
                     title: tt_content.finishersDefinition.EmailToSender.replyToRecipients.item.label
                     el:
                       email:
                         TCEforms:
                           label: tt_content.finishersDefinition.EmailToSender.recipients.email.label
                           config:
                             type: input
                             eval: 'required,email'
                       name:
                         TCEforms:
                           label: tt_content.finishersDefinition.EmailToSender.recipients.name.label
                           config:
                             type: input
               carbonCopyRecipients:
                 title: tt_content.finishersDefinition.EmailToSender.carbonCopyRecipients.label
                 type: array
                 section: true
                 sectionItemKey: email
                 sectionItemValue: name
                 el:
                   _arrayContainer:
                     type: array
                     title: tt_content.finishersDefinition.EmailToSender.carbonCopyRecipients.item.label
                     el:
                       email:
                         TCEforms:
                           label: tt_content.finishersDefinition.EmailToSender.recipients.email.label
                           config:
                             type: input
                             eval: 'required,email'
                       name:
                         TCEforms:
                           label: tt_content.finishersDefinition.EmailToSender.recipients.name.label
                           config:
                             type: input
               blindCarbonCopyRecipients:
                 title: tt_content.finishersDefinition.EmailToSender.blindCarbonCopyRecipients.label
                 type: array
                 section: true
                 sectionItemKey: email
                 sectionItemValue: name
                 el:
                   _arrayContainer:
                     type: array
                     title: tt_content.finishersDefinition.EmailToSender.blindCarbonCopyRecipients.item.label
                     el:
                       email:
                         TCEforms:
                           label: tt_content.finishersDefinition.EmailToSender.recipients.email.label
                           config:
                             type: input
                             eval: 'required,email'
                       name:
                         TCEforms:
                           label: tt_content.finishersDefinition.EmailToSender.recipients.name.label
                           config:
                             type: input
               addHtmlPart:
                 label: tt_content.finishersDefinition.EmailToSender.addHtmlPart.label
                 config:
                   type: check
                   default: 1
               translation:
                 language:
                   label: tt_content.finishersDefinition.EmailToSender.language.label
                   config:
                     type: select
                     renderType: selectSingle
                     minitems: 1
                     maxitems: 1
                     size: 1
                     items:
                       10:
                         - tt_content.finishersDefinition.EmailToSender.language.1
                         - default
               title:
                 label: tt_content.finishersDefinition.EmailToSender.title.label
                 config:
                   type: input

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      .. include:: ../properties/formEngine/elements.rst
