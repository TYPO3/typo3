.. include:: /Includes.rst.txt


.. _config-ref:

=======================
Configuration Reference
=======================

.. _config-ref-yaml:

YAML Configuration Reference
============================

When configuring the CKEditor using YAML, these are the property
names that are currently used:

.. contents::
   :local:
   :depth: 1

processing
----------

Configuring transformations kicks in the RteHtmlParser API of TYPO3, to
only allow certain HTML tags and attributes when saving the database or
leaving the database to the RTE. However, defining transformations towards
RTE is not really necessary anymore. Defining more strict processing options
when storing content in the database also needs to be ensured that CKEditor
allows this functionality too.

This configuration option was previously built within `RTE.proc` and can
still be overridden via Page TSconfig. Everything defined via “processing”
is available in RTE.proc and triggers RteHtmlParser options.

editor
------

Editor contains all RTE-specific options. All CKEditor-specific options, which one
could imagine are available under “config” property and handed over to CKEditor’s
instance-specific config array.

All other sub-properties are usually handled via TYPO3 and then injected in the
CKEditor instance at runtime. This is useful for registering extra plugins, like
the TYPO3 core does with a custom :file:`typo3-link.js` plugin, or adding
third-party plugins like handling images.

editor.config
~~~~~~~~~~~~~

.. option:: editor.config

   Configuration options  For a list of all options see
   https://ckeditor.com/docs/ckeditor5/latest/api/module_core_editor_editorconfig-EditorConfig.html

   .. note::

      Some configuration options from the official CKEditor 5 documentation
      do not apply to TYPO3, since they are related to specific plugins
      (for example: CKBox, CloudServices) which are not bundled in TYPO3's
      CKEditor build.

.. option:: editor.config.language

   defines the editor’s UI language, and is dynamically calculated (if not set otherwise) by
   the backend users’ preference.

.. option:: editor.config.contentsLanguage

   defines the language of the data, which is fetched from the
   sys_language information, but can be overridden by this option as well.
   For referencing files, TYPO3's internal "EXT:" syntax can be used, for
   using language labels, TYPO3's "LLL:" language functionality can be used.

.. option:: editor.config.contentsCss

   defines the CSS file of the editor and the styles that can be applied.

   Example::

      editor.config.contentsCss:
        - "EXT:rte_ckeditor/Resources/Public/Css/contents.css"

   This is the default, as defined in :t3src:`rte_ckeditor/Configuration/RTE/Editor/Base.yaml`.

   .. note::
      When adding custom styling and fonts, all CSS declarations need to be
      prefixed with `.ck-content`. This scoping is applied by TYPO3
      automatically to all custom CSS styles. Referenced CSS stylesheets need to
      be downloadable via :js:`fetch()` in order for the JavaScript-based
      prefixing to work.

.. option:: editor.config.heading

   Defines headings available in the heading dropdown.

   Example::

      heading:
        options:
          - { model: 'heading2', view: 'h2', title: 'Heading 2' }

.. option:: editor.config.style

   Defines styles available in the style dropdown.

   Example::

      style:
        definitions:
          - { name: "Lead", element: "p", classes: ['lead'] }
          - { name: "Multiple", element: "p", classes: ['first', 'second'] }

.. option:: editor.config.importModules

   Imports custom CKEditor plugins. See :t3src:`rte_ckeditor/Configuration/RTE/Editor/Plugins.yaml`
   or :ref:`How do I create a custom plugin? <config-example-customplugin>`
   for examples.

..  _config-linkbrowser:
Link Browser specific options
-----------------------------

There are more configuration options that can be defined in the YAML file of an RTE preset
related to the Link Browser, when managing hyperlinks inside the CKEditor.

Note that the Link Browser can also be displayed based on FormEngine TCA definitions. These
use similar configuration, but from their TCA PHP configuration, and unrelated to the YAML
definition.

The additional example file :t3src:`rte_ckeditor/Configuration/RTE/Editor/LinkBrowser.yaml`
lists all of the following options as an example.

These options are a bit fragmented, it is important to watch for the proper indentation as well
the proper option relation.

..  important::
    Please note that these options are set at the topmost level, and **not** nested inside
    the `editor` YAML structure.

A short overview:

*   `allowedOptions` - allowed list of additional attribute boxes
*   `allowedTypes` - list of allowed Link Types inside the RTE
*   `classesAnchor` - list of default CSS and link target values per Link Type
*   `buttons` - Additional sub-configuration array for specific dropdowns
*   `buttons.link.options` - Global options for the Link Browser
*   `buttons.link.relAttribute` - Configuration for the `rel` attribute block
*   `buttons.link.queryParametersSelector` - Configuration for the `queryParameter` (URI arguments) attribute block
*   `buttons.link.targetSelector` - Configuration for the `target` attribute block
*   `buttons.link.properties.class.allowedClasses` - Allowed additional CSS classes in the `CSS` attribute block
*   `buttons.link.[LinkType].properties.class.default` - Default CSS class per Link Type
*   `classes` - Label definitions for CSS class names

allowedOptions
~~~~~~~~~~~~~~

This string contains a comma separated list of additional attributes used in the Link Browser.
Available field lists can be found in :t3src:`backend/Classes/Controller/AbstractLinkBrowserController.php`,
method :php:`getLinkAttributeFieldDefinitions()`.

Note that the attributes `target`, `class` and `rel` are displayed differently depending on
whether the Link Browser was opened for a TCA element, or a RTE element. See
:t3src:`rte_ckeditor/Classes/Controller/BrowseLinksController.php` in method
`getLinkAttributeFieldDefinitions()`.

Valid attributes keys are:

..  option:: target

   If set, an input box for link target (for example "_blank") is available.

..  option:: title

   If set, entering the link title is available.

..  option:: class

   If set, allowing to enter a CSS class name for the link is available.
   This needs to match the CSS classes made available to the CKEDitor instance.

..  option:: params

   If set, additional parameters are allowed to be set for a link.

..  option:: rel

   If set, relations (:html:`rel` attribute) for links can be set.

To set all of them, you can use:

..  code-block:: yaml
    :caption: MyCKPreset.yml

    allowedOptions: 'target,title,class,params,rel'

To remove all options you can use an empty string:

..  code-block:: yaml
    :caption: MyCKPreset.yml

    allowedOptions: ''

allowedTypes
~~~~~~~~~~~~

This string contains a comma-separated list of all allowed Link Types
for the Link Browser. These are currently:

*   `page`
*   `url`
*   `file`
*   `folder`
*   `email`
*   `...` any custom Link Type

..  code-block:: yaml
    :caption: MyCKPreset.yml

    allowedTypes: 'page,url,file,folder,email,customType'

To remove all types you can use an empty string:

..  code-block:: yaml
    :caption: MyCKPreset.yml

    allowedTypes: ''

classesAnchor
~~~~~~~~~~~~~

This is a sub-array of default CSS classes and target attributes, per Link Type:

..  code-block:: yaml
    :caption: MyCKPreset.yml

   classesAnchor:
    - { class: "customPageCssClass", type: "page", target: "" }
    - { class: "customUrlCssClass", type: "url", target: "_blank" }
    - { class: "customFileCssClass", type: "file", target: "_parent" }
    - { class: "customFolderCssClass", type: "folder" }
    - { class: "customTelephoneCssClass", type: "telephone" }
    - { class: "customEmailCssClass", type: "email" }

Note that the available CSS class here must also be part of the
`buttons.link.properties.class.allowedClasses` definition.

buttons.link
~~~~~~~~~~~~

This structure defines both global options as well as Link Type-specific
options:

buttons.link.options.removeItems
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Can be set to exclude certain Link Types:

..  code-block:: yaml
    :caption: MyCKPreset.yml

    buttons:
        link:
            options:
                removeItems: 'telephone'

buttons.link.relAttribute.enabled
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If the `allowedOptions` string list contains `rel` for setting relation
attributes, this option must also be enabled:

..  code-block:: yaml
    :caption: MyCKPreset.yml

    buttons:
        link:
            relAttribute:
                enabled: true

buttons.link.queryParametersSelector.enabled
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If the `allowedOptions` string list contains `params` for setting URI argument
attributes, this option must also be enabled:

..  code-block:: yaml
    :caption: MyCKPreset.yml

    buttons:
        link:
            queryParametersSelector:
                enabled: true


buttons.link.targetSelector.disabled
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If the `allowedOptions` string list contains `target`, a dropdown is displayed by
default. If you want to hide it, you must set this option to `true`:

..  code-block:: yaml
    :caption: MyCKPreset.yml

    buttons:
        link:
            targetSelector:
                disabled: true

buttons.link.properties.class.required
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A CSS class selection can be forced, so that it may not be empty:

..  code-block:: yaml
    :caption: MyCKPreset.yml

    buttons:
        link:
            properties:
                class:
                    required: true

buttons.link.properties.class.allowedClasses
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is the most vital CSS class selection list, based on a comma-separated
string naming all CSS classes that are allowed. Default CSS classes per Link Type
can only be selected, if they are part of this list.

The names of the CSS classes can be adjusted via the `classes` top-level configuration
hierarchy (see below)

..  code-block:: yaml
    :caption: MyCKPreset.yml

    buttons:
        link:
            properties:
                class:
                    allowedClasses: 'globalCss1,globalCss1,CustomPageCssClass'

buttons.link.[linkType].properties.class.default
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

For each Link Type, a default CSS class can be defined, using the name of the
Link Type as a key:

..  code-block:: yaml
    :caption: MyCKPreset.yml

    buttons:
        link:
            telephone:
                class:
                    default: "customTelephoneCssClass"
            email:
                class:
                    default: "customEmailCssClass"

Note that the CSS class listed here must also be contained in
`buttons.link.properties.class.allowedClasses`.

classes.[CssClassName]
~~~~~~~~~~~~~~~~~~~~~~

The list of CSS classes defined in `buttons.link.properties.class.allowedClasses`
can set a custom label as well as a styling the select option. Note that styling
select options does not work in every browser, and is not suggested to use.

The name of the structure key must match the CSS class name, with a sub-structure
defining `name` (the actual label) and `value` (the possible CSS styling of the option
inside the dropdown):

..  code-block:: yaml
    :caption: MyCKPreset.yml

    classes:
        globalCss1:
            name: "A Label for globalCss1"
            value: "color: red"
        customEmailCssClass:
            name: "An email-specific class for VIPs"

.. _config-ref-tsconfig:

Page TSconfig
=============

We recommend you to put all configurations for the preset in the
:ref:`YAML <config-typo3-yaml>` configuration. However, it is still possible to
override these settings through the page TSconfig.

You can find a list of configuration properties in the :ref:`Page TSconfig
reference, chapter RTE <t3tsconfig:pageTsRte>`.
