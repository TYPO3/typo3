.. include:: ../../Includes.txt

======================================================
Breaking: #79464 - EXT:form - Refactor fluid rendering
======================================================

See :issue:`79464`

Description
===========

EXT:form uses "fluid" as the default rendering strategy.
Therefore, EXT:form has to work closely with the concepts of fluid to avoid current and future problems.
Until now, EXT:form tried to reuse a fluid view instance by reconfiguring the instance on each nesting level, but fluid is not intended for such a purpose.
This change reduces the complexity of the rendering process and works closer with the concepts of fluid.


Impact
======

The configuration options `renderingOptions.templateRootPaths`, `renderingOptions.partialRootPaths` and `renderingOptions.layoutRootPaths` for form elements are
from now on only rules for the root form element ('Form') and will be applied for all child form elements.
If you configure `renderingOptions.templateRootPaths` etc. for other form elements it will have no effect.

The configuration option `renderingOptions.templatePathAndFilename` for form elements was removed from the configuration and will have no effect.
To define a template file name which should be used instead of a filename which is named like the form element type, there is a new option `renderingOptions.templateName`.

The internal setting `renderingOptions.renderableNameInTemplate` for form elements has been removed from the configuration and will have no effect.

The setting `rendererClassName` for form elements are from now on only rules for the root form element ('Form').
If you define this option for other form elements, an `invalid configuration` exception will be thrown.

The configuration for the backend editor inline templates which are used by editor javascript has changed.
The configuration path `prototypes.<prototypeName>.formEditor.formEditorTemplates` has been renamed and has no effect anymore.
The fluid configuration part moved from `prototypes.<prototypeName>.formEditor.formEditorTemplates` to a new section `prototypes.<prototypeName>.formEditor.formEditorFluidConfiguration`.
The backend editor inline template mapping moved to a new section `prototypes.<prototypeName>.formEditor.formEditorPartials`.
The inline template mapping for stage templates has been condensed. If you define custom form editor stage templates which use a default stage template it could
result in a javascript error within the form editor.

The template files moved from `Resources/Private/Frontend/Templates/FormElements/` to `Resources/Private/Frontend/Partials`.
The template structure has changed. Without adaptation of your overridden templates, no form elements are visible within the frontend.


Affected Installations
======================

All installations since TYPO3 8.5 which use the new EXT:form extension and create or extend custom form elements through configuration and / or
override EXT:form template files.


Migration
=========

If you override/ extend

.. code-block:: typoscript

    TYPO3.CMS.Form.mixins.formElementMixins.BaseFormElementMixin.renderingOptions.templateRootPaths
    TYPO3.CMS.Form.mixins.formElementMixins.BaseFormElementMixin.renderingOptions.partialRootPaths
    TYPO3.CMS.Form.mixins.formElementMixins.BaseFormElementMixin.renderingOptions.layoutRootPaths

move it to

.. code-block:: typoscript

    TYPO3.CMS.Form.prototypes.<prototypeName>.formElementsDefinition.Form.renderingOptions.templateRootPaths
    TYPO3.CMS.Form.prototypes.<prototypeName>.formElementsDefinition.Form.renderingOptions.partialRootPaths
    TYPO3.CMS.Form.prototypes.<prototypeName>.formElementsDefinition.Form.renderingOptions.layoutRootPaths


If you override/ extend

.. code-block:: typoscript

    TYPO3.CMS.Form.mixins.formElementMixins.BaseFormElementMixin.renderingOptions.skipUnknownElements

move it to

.. code-block:: typoscript

    TYPO3.CMS.Form.prototypes.<prototypeName>.formElementsDefinition.Form.renderingOptions.skipUnknownElements


If you defined

.. code-block:: typoscript

    TYPO3.CMS.Form.prototypes.<prototypeName>.formElementsDefinition.<formElementType>.rendererClassName

for a <formElementType> which is *NOT* 'Form', you have to remove this setting.


If you defined

.. code-block:: typoscript

    TYPO3.CMS.Form.prototypes.<prototypeName>.formElementsDefinition.Form.renderingOptions.renderableNameInTemplate

you have to use

.. code-block:: typoscript

   TYPO3.CMS.Form.prototypes.<prototypeName>.formElementsDefinition.Form.renderingOptions.templateName

`templateName` is the partial path, relative to `TYPO3.CMS.Form.prototypes.<prototypeName>.formElementsDefinition.Form.renderingOptions.partialRootPaths`


If you defined custom form editor templates within

.. code-block:: typoscript

    TYPO3.CMS.Form.prototypes.<prototypeName>.formEditor.formEditorTemplates

you have to move this to

.. code-block:: typoscript

    TYPO3.CMS.Form.prototypes.<prototypeName>.formEditor.formEditorPartials


If you defined a custom form editor stage template which depends on a default form editor stage template you have to redefine it:

.. code-block:: none

    Stage/Text => Stage/SimpleTemplate
    Stage/Password => Stage/SimpleTemplate
    Stage/AdvancedPassword => Stage/SimpleTemplate
    Stage/Textarea => Stage/SimpleTemplate
    Stage/Checkbox => Stage/SimpleTemplate
    Stage/MultiCheckbox => Stage/SelectTemplate
    Stage/MultiSelect => Stage/SelectTemplate
    Stage/RadioButton => Stage/SelectTemplate
    Stage/SingleSelect => Stage/SelectTemplate
    Stage/DatePicker => Stage/SimpleTemplate
    Stage/Hidden => Stage/SimpleTemplate
    Stage/FileUpload => Stage/FileUploadTemplate
    Stage/ImageUpload => Stage/FileUploadTemplate


All form element templates except the template for the `Form` element moved from templates to partials.
You have to move this too, if you extended the fluid search paths.
The 'Form' element is a template and will be found through `TYPO3.CMS.Form.prototypes.<prototypeName>.formElementsDefinition.Form.renderingOptions.templateRootPaths`.
All other form elements are partials and will be found through `TYPO3.CMS.Form.prototypes.<prototypeName>.formElementsDefinition.Form.renderingOptions.partialRootPaths`.


The template/partial structure has changed. You have to adapt this to your custom templates.
Please look at the files within `EXT:form/Resources/Private/Frontend/Partials`
to see what has happened.
The main change is that you have to wrap the markup with

.. code-block:: xml

    <formvh:renderRenderable renderable="{element}">
        some form element
    </formvh:renderRenderable>

.. index:: Backend, Frontend, ext:form
