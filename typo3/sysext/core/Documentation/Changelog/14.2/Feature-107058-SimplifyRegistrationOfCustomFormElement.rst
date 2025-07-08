..  include:: /Includes.rst.txt

..  _feature-107058-1769168658:

=================================================================
Feature: #107058 - Simplify registration of a Custom Form Element
=================================================================

See :issue:`107058`

Description
===========

The registration of custom form elements in the TYPO3 Form Framework has been
significantly simplified. Previously, registering a custom form element required
subscribing to the JavaScript event :js:`view/stage/abstract/render/template/perform`
to render the element in the Form Editor's stage area.

With this improvement, custom form elements can now be registered without any
custom JavaScript code. The Form Editor automatically uses a generic Web Component
to render form elements in the stage area.

To use this simplified registration method, simply omit the :yaml:`formEditorPartials`
configuration in your form element's YAML definition. The Form Editor will then
automatically render the element using the built-in :html:`<typo3-form-form-element-stage-item>`
Web Component, which provides:

*   Element type and identifier display
*   Element label with required indicator
*   Validators visualization
*   Support for select options (SingleSelect, MultiSelect, RadioButton, Checkbox)
*   Support for allowed MIME types (FileUpload, ImageUpload)
*   Element toolbar
*   Hidden state visualization

The generic rendering automatically extracts and displays relevant information
from your form element's configuration without requiring any custom template or
JavaScript code.

Impact
======

Extension developers can now register custom form elements with minimal
configuration. By omitting the :yaml:`formEditorPartials` configuration, the
Form Editor will automatically render the element using a generic Web Component,
eliminating the need for:

*   Custom Fluid templates in :file:`Resources/Private/Backend/Partials/FormEditor/Stage/`
*   Custom JavaScript code subscribing to :js:`view/stage/abstract/render/template/perform`
*   Manual element rendering logic

This significantly reduces the complexity and maintenance burden when creating
custom form elements that don't require special visualization in the Form Editor.

For custom form elements that require specialized rendering or custom interactions
in the stage area, the :yaml:`formEditorPartials` configuration can still be used
to provide custom Fluid templates, which will work as before.

For a complete step-by-step tutorial on creating custom form elements, see
:ref:`Creating a Custom Form Element typo3/cms-form:howtos-custom-form-element>.

..  index:: Backend, ext:form
