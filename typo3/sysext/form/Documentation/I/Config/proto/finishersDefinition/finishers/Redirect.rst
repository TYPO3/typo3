.. include:: /Includes.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect:

==========
[Redirect]
==========

.. _prototypes.prototypeIdentifier.finishersdefinitionredirect-properties:

Properties
==========


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.implementationclassname:

implementationClassName
-----------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.implementationClassName

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

         Redirect:
           implementationClassName: TYPO3\CMS\Form\Domain\Finishers\RedirectFinisher

:aspect:`Good to know`
      - :ref:`"Custom finisher implementations"<concepts-finishers-customfinisherimplementations>`

:aspect:`Description`
      .. include:: ../properties/implementationClassName.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.options.pageuid:

options.pageUid
---------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.options.pageUid

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      1

:aspect:`Good to know`
      - :ref:`"Redirect finisher"<apireference-finisheroptions-redirectfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Redirect to this page uid.


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.options.additionalparameters:

options.additionalParameters
----------------------------

:aspect:`Option path`
      prototypes.prototypeIdentifier.finishersDefinition.Redirect.options.additionalParameters

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Good to know`
      - :ref:`"Redirect finisher"<apireference-finisheroptions-redirectfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Additional parameters which should be used on the target page.


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.options.fragment:

options.fragment
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.options.fragment

:aspect:`Data type`
      string

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Good to know`
      - :ref:`"Redirect finisher"<apireference-finisheroptions-redirectfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      Add a fragment (e.g. :html:`#c9` or :html:`#foo`) to the redirect link.
      The :html:`#` character can be omitted.


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.options.delay:

options.delay
-------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.options.delay

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      0

:aspect:`Good to know`
      - :ref:`"Redirect finisher"<apireference-finisheroptions-redirectfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      The redirect delay in seconds.


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.options.statuscode:

options.statusCode
------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.options.statusCode

:aspect:`Data type`
      int

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      303

:aspect:`Good to know`
      - :ref:`"Redirect finisher"<apireference-finisheroptions-redirectfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`

:aspect:`Description`
      The HTTP status code for the redirect. Default is "303 See Other".


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.options.translation.propertiesExcludedFromTranslation:

options.translation.propertiesExcludedFromTranslation
-----------------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.options.translation.propertiesExcludedFromTranslation

:aspect:`Data type`
      array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Redirect finisher"<apireference-finisheroptions-redirectfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      Localization from translation files will be skipped for all specified finisher options.


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.options.translation.translationfiles:

options.translation.translationFiles
------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.options.translation.translationFiles

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Frontend

:aspect:`Mandatory`
      No

:aspect:`Default value`
      undefined

:aspect:`Good to know`
      - :ref:`"Redirect finisher"<apireference-finisheroptions-redirectfinisher>`
      - :ref:`"Accessing form runtime values"<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>`
      - :ref:`"Translate finisher options"<concepts-frontendrendering-translation-finishers>`

:aspect:`Description`
      If set, this translation file(s) will be used for finisher option translations.
      If not set, the translation file(s) from the 'Form' element will be used.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.formeditor.iconidentifier:

formeditor.iconIdentifier
-------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.formEditor.iconIdentifier

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

         Redirect:
           formEditor:
             iconIdentifier: form-finisher
             label: formEditor.elements.Form.finisher.Redirect.editor.header.label
             predefinedDefaults:
               options:
                 pageUid: ''
                 additionalParameters: ''
                 fragment: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/iconIdentifier.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.formeditor.label:

formeditor.label
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.formEditor.label

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

         Redirect:
           formEditor:
             iconIdentifier: form-finisher
             label: formEditor.elements.Form.finisher.Redirect.editor.header.label
             predefinedDefaults:
               options:
                 pageUid: ''
                 additionalParameters: ''
                 fragment: ''

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      .. include:: ../properties/label.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.formeditor.predefineddefaults:

formeditor.predefinedDefaults
-----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.formEditor.predefinedDefaults

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

         Redirect:
           formEditor:
             iconIdentifier: form-finisher
             label: formEditor.elements.Form.finisher.Redirect.editor.header.label
             predefinedDefaults:
               options:
                 pageUid: ''
                 additionalParameters: ''
                 fragment: ''

.. :aspect:`Good to know`
      ToDo

:aspect:`Description`
      .. include:: ../properties/predefinedDefaults.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.formengine.label:

FormEngine.label
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.FormEngine.label

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

         Redirect:
           FormEngine:
             label: tt_content.finishersDefinition.Redirect.label

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      .. include:: ../properties/formEngine/label.rst.txt


.. _prototypes.prototypeIdentifier.finishersdefinition.redirect.formengine.elements:

FormEngine.elements
-------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.finishersDefinition.Redirect.FormEngine.elements

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

         Redirect:
           FormEngine:
             label: tt_content.finishersDefinition.Redirect.label
             elements:
               pageUid:
                 label: tt_content.finishersDefinition.Redirect.pageUid.label
                 config:
                   type: group
                   allowed: pages
                   size: 1
                   minitems: 1
                   maxitems: 1
                   fieldWizard:
                     recordsOverview:
                       disabled: 1
               additionalParameters:
                 label: tt_content.finishersDefinition.Redirect.additionalParameters.label
                 config:
                   type: input
              fragment:
                label: tt_content.finishersDefinition.Redirect.fragment.label
                config:
                  type: input

:aspect:`Good to know`
      - :ref:`"Translate form plugin settings"<concepts-formplugin-translation-formengine>`

:aspect:`Description`
      .. include:: ../properties/formEngine/elements.rst.txt

