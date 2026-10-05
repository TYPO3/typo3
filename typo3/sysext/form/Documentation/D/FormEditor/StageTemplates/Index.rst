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

..  versionchanged:: 15.0
    :changelog: breaking-109783-1776735296

    The Core Fluid partials in
    :file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/`
    and the rendering helpers :js:`renderSimpleTemplateWithValidators()` and
    :js:`renderSelectTemplates()` were removed. See
    :ref:`removed stage templates <form-not-found-stage-templates>` for the
    migration.
