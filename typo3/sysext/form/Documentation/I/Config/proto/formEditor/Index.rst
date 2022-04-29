.. include:: /Includes.rst.txt


.. _prototypes.<prototypeidentifier>.formeditor:

============
[formEditor]
============


.. _prototypes.<prototypeidentifier>.formeditor-properties:

Properties
==========

.. _prototypes.<prototypeidentifier>.formeditor.translationfiles:

translationFiles
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.translationFiles

:aspect:`Data type`
      string/ array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2

         formEditor:
           translationFiles:
             10: 'EXT:form/Resources/Private/Language/Database.xlf'

:aspect:`Good to know`
      - :ref:`"Translate form editor settings"<concepts-formeditor-translation-formeditor>`

:aspect:`Description`
      Filesystem path(s) to translation files which should be searched for form editor translations.


.. _prototypes.<prototypeidentifier>.formeditor.dynamicjavascriptmodules.app:

dynamicJavaScriptModules.app
----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.dynamicJavaScriptModules.app

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         formEditor:
           dynamicJavaScriptModules:
             app: TYPO3/CMS/Form/Backend/FormEditor
             mediator: TYPO3/CMS/Form/Backend/FormEditor/Mediator
             viewModel: TYPO3/CMS/Form/Backend/FormEditor/ViewModel

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      ES6 module specifier for the form editor JavaScript app.


.. _prototypes.<prototypeidentifier>.formeditor.dynamicjavascriptmodules.mediator:

dynamicJavaScriptModules.mediator
---------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.dynamicJavaScriptModules.mediator

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 4

         formEditor:
           dynamicJavaScriptModules:
             app: TYPO3/CMS/Form/Backend/FormEditor
             mediator: TYPO3/CMS/Form/Backend/FormEditor/Mediator
             viewModel: TYPO3/CMS/Form/Backend/FormEditor/ViewModel


:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      ES6 module specifier for the form editor JavaScript mediator.


.. _prototypes.<prototypeidentifier>.formeditor.dynamicjavascriptmodules.viewmodel:

dynamicJavaScriptModules.viewModel
----------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.dynamicJavaScriptModules.viewModel

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 5

         formEditor:
           dynamicJavaScriptModules:
             app: TYPO3/CMS/Form/Backend/FormEditor
             mediator: TYPO3/CMS/Form/Backend/FormEditor/Mediator
             viewModel: TYPO3/CMS/Form/Backend/FormEditor/ViewModel


:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      ES6 module specifier for the form editor JavaScript view model.


.. _prototypes.<prototypeidentifier>.formeditor.dynamicjavascriptmodules.additionalviewmodelmodules:

dynamicJavaScriptModules.additionalViewModelModules
---------------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.dynamicJavaScriptModules.additionalViewModelModules

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value (prototype 'standard')`
      undefined

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`
      - :ref:`"Register custom JavaScript modules"<concepts-formeditor-basicjavascriptconcepts-registercustomjavascriptmodules>`

:aspect:`Description`
      Array with ES6 module specifiers for custom JavaScript modules.


.. _prototypes.<prototypeidentifier>.formeditor.addinlinesettings:

addInlineSettings
-----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.addInlineSettings

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      No

:aspect:`Default value (prototype 'standard')`
      undefined

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      Adds Javascript Inline Setting. This will occur in TYPO3.settings - object.


.. _prototypes.<prototypeidentifier>.formeditor.maximumundosteps:

maximumUndoSteps
----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.maximumUndoSteps

:aspect:`Data type`
      int

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2

         formEditor:
           maximumUndoSteps: 10

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      Define the maximum possible undo steps within the form editor.


.. _prototypes.<prototypeidentifier>.formeditor.stylesheets:

stylesheets
-----------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.stylesheets

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2-

         formEditor:
           stylesheets:
             200: 'EXT:form/Resources/Public/Css/form.css'

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      The CSS files to be used by the ``form editor``.


.. _prototypes.<prototypeidentifier>.formeditor.formeditorfluidconfiguration:

formEditorFluidConfiguration
----------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.formEditorFluidConfiguration

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2-

         formEditor:
           formEditorFluidConfiguration:
             templatePathAndFilename: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/InlineTemplates.html'
             partialRootPaths:
               10: 'EXT:form/Resources/Private/Backend/Partials/FormEditor/'
             layoutRootPaths:
               10: 'EXT:form/Resources/Private/Backend/Layouts/FormEditor/'

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`
      - :ref:`"view/inspector/editor/insert/perform"<apireference-formeditor-basicjavascriptconcepts-events-view-inspector-editor-insert-perform>`

:aspect:`Description`
      Basic fluid template search path configurations.


.. _prototypes.<prototypeidentifier>.formeditor.formeditorfluidconfiguration.templatepathandfilename:

formEditorFluidConfiguration.templatePathAndFilename
----------------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.formEditorFluidConfiguration.templatePathAndFilename

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 3

         formEditor:
           formEditorFluidConfiguration:
             templatePathAndFilename: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/InlineTemplates.html'
             partialRootPaths:
               10: 'EXT:form/Resources/Private/Backend/Partials/FormEditor/'
             layoutRootPaths:
               10: 'EXT:form/Resources/Private/Backend/Layouts/FormEditor/'

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      Internal setting. Template which render the inline HTML templates which are used by the form editor JavaScript.


.. _prototypes.<prototypeidentifier>.formeditor.formeditorfluidconfiguration.partialrootpaths:

formEditorFluidConfiguration.partialRootPaths
---------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.formEditorFluidConfiguration.partialRootPaths

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 4-5

         formEditor:
           formEditorFluidConfiguration:
             templatePathAndFilename: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/InlineTemplates.html'
             partialRootPaths:
               10: 'EXT:form/Resources/Private/Backend/Partials/FormEditor/'
             layoutRootPaths:
               10: 'EXT:form/Resources/Private/Backend/Layouts/FormEditor/'

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`
      - :ref:`"view/inspector/editor/insert/perform"<apireference-formeditor-basicjavascriptconcepts-events-view-inspector-editor-insert-perform>`

:aspect:`Description`
      Array with fluid partial search paths for the inline HTML templates which are used by the form editor JavaScript.


.. _prototypes.<prototypeidentifier>.formeditor.formeditorfluidconfiguration.layoutrootpaths:

formEditorFluidConfiguration.layoutRootPaths
--------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.formEditorFluidConfiguration.layoutRootPaths

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 6-7

         formEditor:
           formEditorFluidConfiguration:
             templatePathAndFilename: 'EXT:form/Resources/Private/Backend/Templates/FormEditor/InlineTemplates.html'
             partialRootPaths:
               10: 'EXT:form/Resources/Private/Backend/Partials/FormEditor/'
             layoutRootPaths:
               10: 'EXT:form/Resources/Private/Backend/Layouts/FormEditor/'

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`
      - :ref:`"view/inspector/editor/insert/perform"<apireference-formeditor-basicjavascriptconcepts-events-view-inspector-editor-insert-perform>`

:aspect:`Description`
      Internal setting.  Array with fluid layout search paths.


.. _prototypes.<prototypeidentifier>.formeditor.formeditorpartials:

formEditorPartials
------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.formEditorPartials

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2-

         formEditor:
           formEditorPartials:
             FormElement-_ElementToolbar: Stage/_ElementToolbar
             FormElement-_UnknownElement: Stage/_UnknownElement
             FormElement-Page: Stage/Page
             FormElement-SummaryPage: Stage/SummaryPage
             FormElement-Fieldset: Stage/Fieldset
             FormElement-GridRow: Stage/Fieldset
             FormElement-Text: Stage/SimpleTemplate
             FormElement-Password: Stage/SimpleTemplate
             FormElement-AdvancedPassword: Stage/SimpleTemplate
             FormElement-Textarea: Stage/SimpleTemplate
             FormElement-Checkbox: Stage/SimpleTemplate
             FormElement-MultiCheckbox: Stage/SelectTemplate
             FormElement-MultiSelect: Stage/SelectTemplate
             FormElement-RadioButton: Stage/SelectTemplate
             FormElement-SingleSelect: Stage/SelectTemplate
             FormElement-DatePicker: Stage/SimpleTemplate
             FormElement-StaticText: Stage/StaticText
             FormElement-Hidden: Stage/SimpleTemplate
             FormElement-ContentElement: Stage/ContentElement
             FormElement-FileUpload: Stage/FileUploadTemplate
             FormElement-ImageUpload: Stage/FileUploadTemplate
             FormElement-Email: 'Stage/SimpleTemplate'
             FormElement-Telephone: 'Stage/SimpleTemplate'
             FormElement-Url: 'Stage/SimpleTemplate'
             FormElement-Number: 'Stage/SimpleTemplate'
             FormElement-Date: 'Stage/SimpleTemplate'
             Modal-InsertElements: Modals/InsertElements
             Modal-InsertPages: Modals/InsertPages
             Modal-ValidationErrors: Modals/ValidationErrors
             Inspector-FormElementHeaderEditor: Inspector/FormElementHeaderEditor
             Inspector-CollectionElementHeaderEditor: Inspector/CollectionElementHeaderEditor
             Inspector-TextEditor: Inspector/TextEditor
             Inspector-PropertyGridEditor: Inspector/PropertyGridEditor
             Inspector-SingleSelectEditor: Inspector/SingleSelectEditor
             Inspector-MultiSelectEditor: Inspector/MultiSelectEditor
             Inspector-GridColumnViewPortConfigurationEditor: Inspector/GridColumnViewPortConfigurationEditor
             Inspector-TextareaEditor: Inspector/TextareaEditor
             Inspector-RemoveElementEditor: Inspector/RemoveElementEditor
             Inspector-FinishersEditor: Inspector/FinishersEditor
             Inspector-ValidatorsEditor: Inspector/ValidatorsEditor
             Inspector-RequiredValidatorEditor: Inspector/RequiredValidatorEditor
             Inspector-CheckboxEditor: Inspector/CheckboxEditor
             Inspector-ValidationErrorMessageEditor: 'Inspector/ValidationErrorMessageEditor'
             Inspector-Typo3WinBrowserEditor: Inspector/Typo3WinBrowserEditor

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`
      - :ref:`"Common Abstract view formelement templates"<apireference-formeditor-stage-commonabstractformelementtemplates>`
      - :ref:`"available inspector editors"<prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formEditor.editors-availableinspectoreditors>`
      - :ref:`"view/inspector/editor/insert/perform"<apireference-formeditor-basicjavascriptconcepts-events-view-inspector-editor-insert-perform>`

:aspect:`Description`
      Array with mappings for the inline HTML templates. The keys are identifiers which could be used within the JavaScript code. The values are partial paths, relative to :ref:`"prototypes.\<prototypeIdentifier>.formeditor.formEditorFluidConfiguration.partialRootPaths"<prototypes.\<prototypeidentifier>.formeditor.formeditorfluidconfiguration.partialrootpaths>`.
      The partials content will be rendered as inline HTML. This inline HTML templates can be identified and used by such a key (e.g. "Inspector-TextEditor") within the JavaScript code.


.. _prototypes.<prototypeidentifier>.formeditor.formelementpropertyvalidatorsdefinition:

formElementPropertyValidatorsDefinition
---------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.formElementPropertyValidatorsDefinition

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2-

         formEditor:
           formElementPropertyValidatorsDefinition:
             NotEmpty:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.NotEmpty.label
             Integer:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.Integer.label
             NaiveEmail:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.NaiveEmail.label
             NaiveEmailOrEmpty:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.NaiveEmail.label
             FormElementIdentifierWithinCurlyBracesInclusive:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.FormElementIdentifierWithinCurlyBraces.label
             FormElementIdentifierWithinCurlyBracesExclusive:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.FormElementIdentifierWithinCurlyBraces.label
             FileSize:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.FileSize.label
             RFC3339FullDate:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.RFC3339FullDate.label
             RegularExpressionPattern:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.RegularExpressionPattern.label

:aspect:`Related options`
      - :ref:`"[TextEditor] propertyValidators"<prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.propertyvalidators-texteditor>`
      - :ref:`"[Typo3WinBrowserEditor] propertyValidators"<prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.propertyvalidators-typo3winbrowsereditor>`

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      Some inspector editors are able to validate it's values through a JavaScript methods.
      ``formElementPropertyValidatorsDefinition`` define basic configurations for such JavaScript validators.
      This JavaScript validators can be registered through ``getFormEditorApp().addPropertyValidationValidator()``. The first method argument is the identifier
      for this validator. Every array key within ``formElementPropertyValidatorsDefinition`` must be equal to such a identifier.


.. _prototypes.<prototypeidentifier>.formeditor.formelementpropertyvalidatorsdefinition.<formelementpropertyvalidatoridentifier>.errormessage:

formElementPropertyValidatorsDefinition.<formElementPropertyValidatorIdentifier>.errorMessage
---------------------------------------------------------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.formElementPropertyValidatorsDefinition.<formElementPropertyValidatorIdentifier>.errorMessage

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 4, 6, 8, 10, 12, 14

         formEditor:
           formElementPropertyValidatorsDefinition:
             NotEmpty:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.NotEmpty.label
             Integer:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.Integer.label
             NaiveEmail:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.NaiveEmail.label
             NaiveEmailOrEmpty:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.NaiveEmail.label
             FormElementIdentifierWithinCurlyBracesInclusive:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.FormElementIdentifierWithinCurlyBraces.label
             FormElementIdentifierWithinCurlyBracesExclusive:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.FormElementIdentifierWithinCurlyBraces.label
             FileSize:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.FileSize.label
             RFC3339FullDate:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.RFC3339FullDate.label
             RegularExpressionPattern:
               errorMessage: formEditor.formElementPropertyValidatorsDefinition.RegularExpressionPattern.label

:aspect:`Related options`
      - :ref:`"[TextEditor] propertyValidators"<prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.propertyvalidators-texteditor>`
      - :ref:`"[Typo3WinBrowserEditor] propertyValidators"<prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.propertyvalidators-typo3winbrowsereditor>`

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      The error message for a inspector editor property validator which is shown if the validation fails.


.. _prototypes.<prototypeidentifier>.formeditor.formelementgroups:

formElementGroups
-----------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.formElementGroups

:aspect:`Data type`
      array

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 2-

         formEditor:
           formElementGroups:
             input:
               label: formEditor.formElementGroups.input.label
             html5:
               label: 'formEditor.formElementGroups.html5.label'
             select:
               label: formEditor.formElementGroups.select.label
             custom:
               label: formEditor.formElementGroups.custom.label
             container:
               label: formEditor.formElementGroups.container.label
             page:
               label: formEditor.formElementGroups.page.label

:aspect:`Related options`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.AdvancedPassword.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.advancedpassword.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Checkbox.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.checkbox.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.ContentElement.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.contentelement.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Date.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.date.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.DatePicker.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.datepicker.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Email.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.email.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Fieldset.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.fieldset.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.FileUpload.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.fileupload.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.GridRow.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.gridrow.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Hidden.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.hidden.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.ImageUpload.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.imageupload.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.MultiCheckbox.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.multicheckbox.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.MultiSelect.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.multiselect.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Number.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.number.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Page.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.page.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Password.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.password.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.RadioButton.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.radiobutton.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.SingleSelect.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.singleselect.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.StaticText.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.statictext.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.SummaryPage.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.summarypage.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Telephone.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.telephone.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Text.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.text.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Textarea.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.textarea.formeditor.group>`
      - :ref:`"prototypes.\<prototypeIdentifier>.formElementsDefinition.Url.formEditor.group"<prototypes.\<prototypeIdentifier>.formelementsdefinition.url.formeditor.group>`

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      Every form element can be placed within a group within the ``form editor`` "new Element" modal.
      Every form element which should be shown within such a group, must have a ``group`` property. The form element ``group`` property value
      must be equal to an array key within ``formElementGroups``.


.. _prototypes.<prototypeidentifier>.formeditor.formelementgroups.<formelementgroupidentifier>.label:

formElementGroups.<formElementGroupIdentifier>.label
----------------------------------------------------

:aspect:`Option path`
      prototypes.<prototypeIdentifier>.formeditor.formElementGroups.<formElementGroupIdentifier>.label

:aspect:`Data type`
      string

:aspect:`Needed by`
      Backend (form editor)

:aspect:`Mandatory`
      Yes

:aspect:`Default value (prototype 'standard')`
      .. code-block:: yaml
         :linenos:
         :emphasize-lines: 4, 6, 8, 10, 12

         formEditor:
           formElementGroups:
             input:
               label: formEditor.formElementGroups.input.label
             select:
               label: formEditor.formElementGroups.select.label
             custom:
               label: formEditor.formElementGroups.custom.label
             container:
               label: formEditor.formElementGroups.container.label
             page:
               label: formEditor.formElementGroups.page.label

:aspect:`Good to know`
      - :ref:`"Form editor"<concepts-formeditor>`

:aspect:`Description`
      The label for a group within the ``form editor`` "new Element" modal.
