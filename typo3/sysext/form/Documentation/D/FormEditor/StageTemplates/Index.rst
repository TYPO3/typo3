..  include:: /Includes.rst.txt

..  _apireference-formeditor-stage-commonabstractformelementtemplates:
..  _apireference-formeditor-stagetemplates:

===============
Stage templates
===============

The **Stage** component renders each form element as an HTML item in the
abstract view. This section explains the two rendering strategies: the
modern web-component approach (recommended) and the legacy Fluid-partial
approach (deprecated).

..  contents::
    :depth: 1
    :local:


..  _apireference-formeditor-stagetemplates-webcomponent:

Built-in web component (recommended)
=====================================

When no :yaml:`formEditorPartials` entry exists for a form element type,
the Stage component automatically renders it using the built-in
:html:`<typo3-form-form-element-stage-item>` web component. The component
displays the element's label, type icon, validators, select options and
allowed MIME types without requiring any custom JavaScript.

..  tip::
    For most custom form elements this is the recommended approach. Simply
    omit :yaml:`formEditorPartials` from the prototype configuration and the
    editor handles the rest.

Properties set on the web component from the :js:`FormElement` model:

.. list-table::
   :header-rows: 1
   :widths: 30 70

   *  -  Property
      -  Source in FormElement model
   *  -  :js:`elementType`
      -  Form element definition :yaml:`label`
   *  -  :js:`elementLabel`
      -  :yaml:`label` (falls back to :yaml:`identifier`)
   *  -  :js:`elementIconIdentifier`
      -  Form element definition :yaml:`iconIdentifier`
   *  -  :js:`validators`
      -  :yaml:`validators` array (excludes ``NotEmpty``, shown via :js:`isRequired`)
   *  -  :js:`isRequired`
      -  ``true`` when a ``NotEmpty`` validator is present
   *  -  :js:`options`
      -  :yaml:`properties.options` (for select-like elements)
   *  -  :js:`allowedMimeTypes`
      -  :yaml:`properties.allowedMimeTypes`
   *  -  :js:`content`
      -  :yaml:`properties.text` or :yaml:`properties.contentElementUid`
   *  -  :js:`isHidden`
      -  ``true`` when :yaml:`renderingOptions.enabled` is ``false``


..  _apireference-formeditor-stagetemplates-fluid:

Custom Fluid partial (advanced)
================================

If you need fully custom stage rendering – for example to display a
proprietary summary of complex properties – you can still provide a Fluid
partial and subscribe to the
:ref:`view/stage/abstract/render/template/perform <apireference-formeditor-jsevents-view-stage-abstract-render-template-perform>`
event to populate it with DOM manipulation.

The core Fluid partials are located in
:file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/`.

..  warning::
    The legacy stage rendering helpers
    :js:`renderSimpleTemplateWithValidators()` and
    :js:`renderSelectTemplates()` from
    :js:`@typo3/form/backend/form-editor/stage-component` are deprecated
    since TYPO3 v14.2 and will be removed in TYPO3 v15. Migrate to the
    web component approach (omit :yaml:`formEditorPartials`) or implement
    custom DOM manipulation in the event subscriber.

..  _apireference-formeditor-stage-commonabstractformelementtemplates-simpletemplate:
..  _apireference-formeditor-stagetemplates-fluid-simpletemplate:

Stage/SimpleTemplate (deprecated)
----------------------------------

Displays the element :yaml:`label`. When the element has validators, a
validator icon and their labels appear on hover/selection. Rendered via
the deprecated :js:`renderSimpleTemplateWithValidators()`.

..  deprecated:: 14.2
    Use the :html:`<typo3-form-form-element-stage-item>` web component
    by omitting :yaml:`formEditorPartials`, or implement custom DOM
    manipulation in the
    :ref:`view/stage/abstract/render/template/perform <apireference-formeditor-jsevents-view-stage-abstract-render-template-perform>`
    subscriber. See Deprecation :issue:`109306`.

..  _apireference-formeditor-stage-commonabstractformelementtemplates-selecttemplate:
..  _apireference-formeditor-stagetemplates-fluid-selecttemplate:

Stage/SelectTemplate (deprecated)
----------------------------------

Extends ``Stage/SimpleTemplate`` by additionally listing the chosen option
labels from :yaml:`properties.options.*`. Rendered via the deprecated
:js:`renderSelectTemplates()`.

Example form element using select options:

..  literalinclude:: _codesnippets/_select-template.yaml
    :language: yaml

The template partial contains a container with the path to read:

..  literalinclude:: _codesnippets/_select-template-partial.html
    :language: html

For elements using a different array property (e.g. ``FileUpload`` with
:yaml:`properties.allowedMimeTypes`), adjust the :html:`data-template-property`
attribute accordingly:

..  literalinclude:: _codesnippets/_file-upload-partial.html
    :language: html

The web component handles both cases automatically.

..  deprecated:: 14.2
    Use the :html:`<typo3-form-form-element-stage-item>` web component
    by omitting :yaml:`formEditorPartials`.
    See `Deprecation: #109306 - Deprecate form editor stage template rendering functions <https://docs.typo3.org/permalink/changelog:deprecation-109306-1774010043>`_.

