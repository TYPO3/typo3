..  include:: /Includes.rst.txt

..  _feature-108966-1738963200:

===============================================================
Feature: #108966 - Rich text editor support in TYPO3 form editor
===============================================================

See :issue:`108966`

Description
===========

The TYPO3 form editor now supports rich text editing for textarea fields with
CKEditor 5. Form elements can be configured to use any available RTE preset,
which provides a consistent editing experience across the TYPO3 backend.

The implementation includes a new
:php:`\TYPO3\CMS\Form\Service\RichTextConfigurationService` that resolves
CKEditor configuration from global TYPO3 RTE presets and prepares it for use
in the form editor context. External plugins, such as the TYPO3 link browser,
are configured automatically.

Impact
======

Form integrators can now enable rich text editing in any textarea field in
the form editor by configuring it in the form YAML configuration.

The following form elements and finishers now support rich text editing out of
the box:

*   StaticText element - formatted text in forms
*   Checkbox element - labels with links for privacy policies, etc.
*   Confirmation finisher - formatted confirmation messages

All textarea fields in custom form elements can be configured to use the RTE.

Basic configuration
-------------------

Enable rich text editing for a form element:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Form/MyFormSetup.yaml

    prototypes:
      standard:
        formElementsDefinition:
          StaticText:
            formEditor:
              editors:
                300:
                  identifier: staticText
                  templateName: Inspector-TextareaEditor
                  label: formEditor.elements.StaticText.editor.staticText.label
                  propertyPath: properties.text
                  enableRichtext: true
                  richtextConfiguration: form-label

The `richtextConfiguration` option accepts any registered RTE preset name, for
example:

*   `form-label` - simple formatting for labels (bold, italic, link) - default
*   `form-content` - extended formatting for content fields (includes lists)
*   `default` - standard TYPO3 RTE with all features
*   `minimal` - minimal feature set

New form RTE presets
--------------------

Two new RTE presets specifically designed for the form extension are now
available:

**form-label**
   Essential formatting options for labels and short text fields.
   Includes: bold, italic, link

**form-content**
   Extended formatting options for content fields like StaticText.
   Includes: bold, italic, link, bulleted lists, numbered lists

Configuration options
---------------------

The following options are available for textarea editors in the form editor:

`enableRichtext`
   :aspect:`Data type`
      boolean

   :aspect:`Default`
      false

   :aspect:`Description`
      Enables rich text editing for this textarea field.

`richtextConfiguration`
   :aspect:`Data type`
      string

   :aspect:`Default`
      `form-label`

   :aspect:`Description`
      Name of the RTE preset to use. The preset must be registered in
      :php:`$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']`.
      Common presets: `form-label`, `form-content`, `default`, `minimal`,
      `full`

Custom sanitizer configuration
------------------------------

The form extension uses a multi-layer sanitization approach for security:

*   **Backend**: Content is sanitized using the `htmlSanitize.build` setting
    from the RTE preset processing configuration.
*   **Frontend**: Content is sanitized again using the `default` sanitizer via
    the `f:sanitize.html()` ViewHelper.

To use a custom sanitizer in the backend, configure it in your RTE preset:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/RTE/MyPreset.yaml

    processing:
      HTMLparser_db:
        htmlSanitize:
          build: \MyVendor\MyExtension\Html\MySanitizerBuilder

Frontend customization
----------------------

The frontend templates use `f:sanitize.html()` with the `default` sanitizer for
defense-in-depth security. To customize the frontend sanitization, integrators
have two options:

**Option 1: Override Fluid templates**

Override the form element templates and specify a custom sanitizer build:

..  code-block:: html
    :caption: EXT:my_extension/Resources/Private/Frontend/Partials/StaticText.html

    {formvh:translateElementProperty(element: element, property: 'text')
        -> f:sanitize.html(build: 'myCustomBuild')
        -> f:transform.html()}

**Option 2: Register a custom default sanitizer**

Register a custom sanitizer builder as the default sanitizer globally:

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['htmlSanitizer']['default']
        = \MyVendor\MyExtension\Html\MySanitizerBuilder::class;

.. index:: Backend, RTE, ext:form, ext:rte_ckeditor
