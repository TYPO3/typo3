.. include:: /Includes.rst.txt


.. _formmanager:

=============
[formManager]
=============


.. _formmanager-properties:

Properties
==========


.. _formmanager.dynamicjavascriptmodules.app:

dynamicJavaScriptModules.app
----------------------------

:aspect:`Option path`
      formManager.dynamicJavaScriptModules.app

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2

         dynamicJavaScriptModules:
           app: TYPO3/CMS/Form/Backend/FormManager
           viewModel: TYPO3/CMS/Form/Backend/FormManager/ViewModel

:aspect:`Description`
      Internal setting. ES6 module specifier for the form manager JavaScript app.


.. _formmanager.dynamicjavascriptmodules.viewmodel:

dynamicJavaScriptModules.viewModel
----------------------------------

:aspect:`Option path`
      formManager.dynamicJavaScriptModules.viewModel

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         dynamicJavaScriptModules:
           app: '@typo3/form/backend/form-manager.js'
           viewModel: '@typo3/form/backend/form-manager/view-model.js'

:aspect:`Description`
      Internal setting. ES6 module specifier for the form manager JavaScript view model.


.. _formmanager.stylesheets:

stylesheets
-----------

:aspect:`Option path`
      formManager.stylesheets

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         stylesheets:
           100: 'EXT:form/Resources/Public/Css/form.css'

:aspect:`Description`
      Internal setting. Path for the form manager CSS file.


.. _formmanager.translationfiles:

translationFiles
----------------

:aspect:`Option path`
      formManager.translationFiles

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         translationFiles:
           10: 'EXT:form/Resources/Private/Language/Database.xlf'

:aspect:`Good to know`
      :ref:`Translate "Start template" options<concepts-formmanager-translation-starttemplate>`

:aspect:`Description`
      The translation file(s) which should be used to translate parts of the form manager.


.. _formmanager.javascripttranslationfile:

javaScriptTranslationFile
-------------------------

:aspect:`Option path`
      formManager.javaScriptTranslationFile

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         javaScriptTranslationFile: 'EXT:form/Resources/Private/Language/locallang_formManager_javascript.xlf'

:aspect:`Description`
      Internal setting. Path for the inline language labels for the form manager app.


.. _formmanager.selectableprototypesconfiguration:

selectablePrototypesConfiguration
---------------------------------

:aspect:`Option path`
      formManager.selectablePrototypesConfiguration

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         selectablePrototypesConfiguration:
           100:
             identifier: standard
             label: formManager.selectablePrototypesConfiguration.standard.label
             newFormTemplates:
               100:
                 templatePath: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml'
                 label: formManager.selectablePrototypesConfiguration.standard.newFormTemplates.blankForm.label
               200:
                 templatePath: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/SimpleContactForm.yaml'
                 label: formManager.selectablePrototypesConfiguration.standard.newFormTemplates.simpleContactForm.label

:aspect:`Good to know`
      - :ref:`"Start templates"<concepts-formmanager-starttemplate>`
      - :ref:`Translate "Start template" options<concepts-formmanager-translation-starttemplate>`

:aspect:`Description`
      Array with numerical Keys. Configure the ``Start template`` selection list within the ``form manager`` "Advanced settings" step.


.. _formmanager.selectableprototypesconfiguration.*.identifier:

selectablePrototypesConfiguration.*.identifier
----------------------------------------------

:aspect:`Option path`
      formManager.selectablePrototypesConfiguration.*.identifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`prototypes <prototypes>`

:aspect:`Good to know`
      - :ref:`"Start templates"<concepts-formmanager-starttemplate>`

:aspect:`Description`
      Reference to a ``prototype`` which should be used for the newly created form definition.


.. _formmanager.selectableprototypesconfiguration.*.label:

selectablePrototypesConfiguration.*.label
-----------------------------------------

:aspect:`Option path`
      formManager.selectablePrototypesConfiguration.*.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Good to know`
      - :ref:`"Start templates"<concepts-formmanager-starttemplate>`
      - :ref:`Translate "Start template" options<concepts-formmanager-translation-starttemplate>`

:aspect:`Description`
      The ``Form prototype`` selectlist label for this ``prototype`` within the ``form manager`` "Advanced settings" step.


.. _formmanager.selectableprototypesconfiguration.*.newformtemplates:

selectablePrototypesConfiguration.*.newFormTemplates
----------------------------------------------------

:aspect:`Option path`
      formManager.selectablePrototypesConfiguration.*.newFormTemplates

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 5-

         selectablePrototypesConfiguration:
           100:
             identifier: standard
             label: formManager.selectablePrototypesConfiguration.standard.label
             newFormTemplates:
               100:
                 templatePath: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/BlankForm.yaml'
                 label: formManager.selectablePrototypesConfiguration.standard.newFormTemplates.blankForm.label
               200:
                 templatePath: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/Yaml/NewForms/SimpleContactForm.yaml'
                 label: formManager.selectablePrototypesConfiguration.standard.newFormTemplates.simpleContactForm.label

:aspect:`Good to know`
      - :ref:`"Start templates"<concepts-formmanager-starttemplate>`
      - :ref:`Translate "Start template" options<concepts-formmanager-translation-starttemplate>`

:aspect:`Description`
      Array with numerical Keys. Configure the ``Start templates`` selectlist for this ``prototype`` within the ``form manager`` "Advanced settings" step.


.. _formmanager.selectableprototypesconfiguration.*.newformtemplates.*.templatepath:

selectablePrototypesConfiguration.*.newFormTemplates.*.templatePath
-------------------------------------------------------------------

:aspect:`Option path`
      formManager.selectablePrototypesConfiguration.*.newFormTemplates.*.templatePath

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Good to know`
      - :ref:`"Start templates"<concepts-formmanager-starttemplate>`
      - :ref:`Translate "Start template" options<concepts-formmanager-translation-starttemplate>`

:aspect:`Description`
      The filesystem path to the `Start template`` YAML file.


.. _formmanager.selectableprototypesconfiguration.*.newformtemplates.*.label:

selectablePrototypesConfiguration.*.newFormTemplates.*.label
------------------------------------------------------------

:aspect:`Option path`
      formManager.selectablePrototypesConfiguration.*.newFormTemplates.*.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Good to know`
      - :ref:`"Start templates"<concepts-formmanager-starttemplate>`
      - :ref:`Translate "Start template" options<concepts-formmanager-translation-starttemplate>`

:aspect:`Description`
      The ``Start template`` selectlist label for this ``Start template`` within the ``form manager`` "Advanced settings" step.


.. _formmanager.controller:

controller
----------

:aspect:`Option path`
      formManager.controller

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         controller:
           deleteAction:
             errorTitle: formManagerController.deleteAction.error.title
             errorMessage: formManagerController.deleteAction.error.body

:aspect:`Description`
      Internal setting. Configure the ``form manager`` flash message texts.


.. _formmanager.controller.deleteaction.errortitle:

controller.deleteAction.errorTitle
----------------------------------

:aspect:`Option path`
      formManager.controller.deleteAction.errorTitle

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         controller:
           deleteAction:
             errorTitle: formManagerController.deleteAction.error.title
             errorMessage: formManagerController.deleteAction.error.body

:aspect:`Description`
      Internal setting. Configure the ``form manager`` flash message texts.


.. _formmanager.controller.deleteaction.errormessage:

controller.deleteAction.errorMessage
------------------------------------

:aspect:`Option path`
      formManager.controller.deleteAction.errorMessage

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 4

         controller:
           deleteAction:
             errorTitle: formManagerController.deleteAction.error.title
             errorMessage: formManagerController.deleteAction.error.body

:aspect:`Description`
      Internal setting. Configure the ``form manager`` flash message texts.
