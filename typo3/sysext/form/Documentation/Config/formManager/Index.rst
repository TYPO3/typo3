.. include:: ../../Includes.txt


.. _typo3.cms.form.formmanager:

=============
[formManager]
=============


.. _typo3.cms.form.formmanager-properties:

Properties
==========


.. _typo3.cms.form.formmanager.dynamicrequirejsmodules.app:

dynamicRequireJsModules.app
---------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.dynamicRequireJsModules.app

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

         dynamicRequireJsModules:
           app: TYPO3/CMS/Form/Backend/FormManager
           viewModel: TYPO3/CMS/Form/Backend/FormManager/ViewModel

:aspect:`Description`
      Internal setting. RequireJS path for the form manager JavaScript app.


.. _typo3.cms.form.formmanager.dynamicrequirejsmodules.viewmodel:

dynamicRequireJsModules.viewModel
---------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.dynamicRequireJsModules.viewModel

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

         dynamicRequireJsModules:
           app: TYPO3/CMS/Form/Backend/FormManager
           viewModel: TYPO3/CMS/Form/Backend/FormManager/ViewModel

:aspect:`Description`
      Internal setting. RequireJS path for the form manager JavaScript view model.


.. _typo3.cms.form.formmanager.stylesheets:

stylesheets
-----------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.stylesheets

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


.. _typo3.cms.form.formmanager.translationfile:

translationFile
---------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.translationFile

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      .. code-block:: yaml
         :linenos:

         translationFile: 'EXT:form/Resources/Private/Language/Database.xlf'

:aspect:`Good to know`
      :ref:`Translate "Start template" options<concepts-formmanager-translation-starttemplate>`

:aspect:`Description`
      The translation file(s) which should be used to translate parts of the form manager.


.. _typo3.cms.form.formmanager.javascripttranslationfile:

javaScriptTranslationFile
-------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.javaScriptTranslationFile

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


.. _typo3.cms.form.formmanager.selectableprototypesconfiguration:

selectablePrototypesConfiguration
---------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.selectablePrototypesConfiguration

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


.. _typo3.cms.form.formmanager.selectableprototypesconfiguration.*.identifier:

selectablePrototypesConfiguration.*.identifier
----------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.selectablePrototypesConfiguration.*.identifier

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form manager)

:aspect:`Mandatory`
      Yes

:aspect:`Related options`
      - :ref:`TYPO3.CMS.Form.prototypes <typo3.cms.form.prototypes>`

:aspect:`Good to know`
      - :ref:`"Start templates"<concepts-formmanager-starttemplate>`

:aspect:`Description`
      Reference to a ``prototype`` which should be used for the newly created form definition.


.. _typo3.cms.form.formmanager.selectableprototypesconfiguration.*.label:

selectablePrototypesConfiguration.*.label
-----------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.selectablePrototypesConfiguration.*.label

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


.. _typo3.cms.form.formmanager.selectableprototypesconfiguration.*.newformtemplates:

selectablePrototypesConfiguration.*.newFormTemplates
----------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.selectablePrototypesConfiguration.*.newFormTemplates

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


.. _typo3.cms.form.formmanager.selectableprototypesconfiguration.*.newformtemplates.*.templatepath:

selectablePrototypesConfiguration.*.newFormTemplates.*.templatePath
-------------------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.selectablePrototypesConfiguration.*.newFormTemplates.*.templatePath

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


.. _typo3.cms.form.formmanager.selectableprototypesconfiguration.*.newformtemplates.*.label:

selectablePrototypesConfiguration.*.newFormTemplates.*.label
------------------------------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.selectablePrototypesConfiguration.*.newFormTemplates.*.label

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


.. _typo3.cms.form.formmanager.controller:

controller
----------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.controller

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


.. _typo3.cms.form.formmanager.controller.deleteaction.errortitle:

controller.deleteAction.errorTitle
----------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.controller.deleteAction.errorTitle

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


.. _typo3.cms.form.formmanager.controller.deleteaction.errormessage:

controller.deleteAction.errorMessage
------------------------------------

:aspect:`Option path`
      TYPO3.CMS.Form.formManager.controller.deleteAction.errorMessage

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
