..  include:: /Includes.rst.txt

..  _deprecation-109306-1774010043:

===============================================================================
Deprecation: #109306 - Deprecate form editor stage template rendering functions
===============================================================================

See :issue:`109306`

Description
===========

The Form Editor stage component provided a set of JavaScript helper functions
for template-based rendering of form elements in the stage area. These
functions were designed to be called from subscribers to the
:js:`view/stage/abstract/render/template/perform` PubSub event, which is the
extension point for custom form element rendering in the stage.

With the introduction of the
:html:`<typo3-form-form-element-stage-item>` and
:html:`<typo3-form-page-stage-item>` web components (see
:ref:`feature-107058-1769168658`), the built-in template-based helper
functions have been superseded. The
:js:`view/stage/abstract/render/template/perform` event
**remains available**, and extension authors may continue to subscribe to it
to implement fully custom stage rendering logic.

The following exported functions from
:js:`@typo3/form/backend/form-editor/stage-component` are deprecated:

*   :js:`eachTemplateProperty()`
*   :js:`renderSimpleTemplate()`
*   :js:`renderSimpleTemplateWithValidators()`
*   :js:`renderCheckboxTemplate()`
*   :js:`renderSelectTemplates()`
*   :js:`renderFileUploadTemplates()`
*   :js:`createAbstractViewFormElementToolbar()` — only used by the legacy
    template-based rendering path. Web component-based elements handle
    their toolbar via the :js:`toolbarConfig` property of
    :html:`<typo3-form-form-element-stage-item>`

In addition, all Fluid partial templates in
:file:`EXT:form/Resources/Private/Backend/Partials/FormEditor/Stage/` are
deprecated, as they were designed for use with the template-based rendering
approach described above:

*   :file:`SimpleTemplate.fluid.html`
*   :file:`SelectTemplate.fluid.html`
*   :file:`FileUploadTemplate.fluid.html`
*   :file:`ContentElement.fluid.html`
*   :file:`Fieldset.fluid.html`
*   :file:`StaticText.fluid.html`
*   :file:`Page.fluid.html`
*   :file:`SummaryPage.fluid.html`
*   :file:`_ElementToolbar.fluid.html`
*   :file:`_UnknownElement.fluid.html`

Impact
======

Extensions that call any of the deprecated helper functions will receive IDE
deprecation hints and TypeScript compiler warnings. The deprecated Fluid
templates will emit an HTML comment in the rendered stage area indicating
their deprecation. All deprecated functions and templates will be removed in
TYPO3 v15.

Affected installations
======================

All extensions that:

*   call any of the deprecated JavaScript helper functions (including
    :js:`createAbstractViewFormElementToolbar()`), typically from a subscriber
    of the :js:`view/stage/abstract/render/template/perform` event, or
*   reference any of the deprecated Fluid partial templates via
    :yaml:`formEditorPartials` in their prototype configuration.


Migration
=========

Two migration paths are available:

**Option 1: Use the built-in web component (recommended)**

Remove the custom JavaScript subscriber and omit the
:yaml:`formEditorPartials` stage partial configuration from your form
element's YAML definition. The Form Editor will then render the element
automatically using the built-in
:html:`<typo3-form-form-element-stage-item>` web component.

See :ref:`feature-107058-1769168658` for full details.

**Option 2: Implement custom rendering logic in the event subscriber**

If you need to keep using the
:js:`view/stage/abstract/render/template/perform` event, replace calls to
the deprecated helper functions with your own DOM manipulation logic.

..  index:: Backend, JavaScript, NotScanned, ext:form
