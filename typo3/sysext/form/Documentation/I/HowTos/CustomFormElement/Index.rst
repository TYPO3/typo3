.. include:: /Includes.rst.txt

.. _howtos-custom-form-element:

==============================
Creating a custom form element
==============================

This tutorial shows you how to create a custom form element for the TYPO3 Form
Framework. We'll create a "Gender Select" element as an example.

.. contents:: Table of Contents
   :depth: 2
   :local:

Prerequisites
=============

Before you start, make sure you have:

* Basic knowledge of YAML configuration
* A sitepackage where you can add configuration files

Step 1: Create the configuration files
======================================

Create a form set directory in your extension. The sub-directory name is
arbitrary — we use ``CustomElement`` here.

File location
-------------

Create the following structure in your extension:

..  code-block:: none

    EXT:my_extension/
      Configuration/
        Form/
          CustomElement/
            config.yaml

Configuration structure
-----------------------

Here's the complete configuration for our Gender Select element:

.. literalinclude:: _CustomFormSetup.yaml
   :language: yaml
   :caption: EXT:my_extension/Configuration/Form/CustomElement/config.yaml (after metadata)

Common inspector editors
~~~~~~~~~~~~~~~~~~~~~~~~

Here are some commonly used inspector editors (:ref:`Inspector <concepts-formeditor-inspector>`) you can add to your form elements:

**Inspector-FormElementHeaderEditor** (100)
    Shows the element header in the inspector panel

**Inspector-TextEditor** (200-300)
    A simple text input field for properties like label and description

**Inspector-CheckboxEditor** (240)
    Adds the checkbox :guilabel:`Field visible`, which sets
    `renderingOptions.enabled`. Custom form elements do not get this
    checkbox automatically. Add the editor to your element to let editors
    hide it.

    ..  versionadded:: 14.1
        :changelog: feature-108663-1737166800

**Inspector-PropertyGridEditor** (400)
    A grid editor for managing key-value pairs (like options)

**Inspector-GridColumnViewPortConfigurationEditor** (700)
    Controls responsive behavior and column widths for different screen sizes

**Inspector-RequiredValidatorEditor** (800)
    Adds a checkbox to make the field required

**Inspector-ValidationErrorMessageEditor** (900)
    Allows customizing validation error messages

**Inspector-RemoveElementEditor** (9999)
    Shows a button to remove the element from the form

..  _howtos-custom-form-element-values:

Submitted values in finishers and on the summary page
-----------------------------------------------------

..  versionchanged:: 14.3.7
    :changelog: important-106903-1787754756

    Before, all finisher options used the submitted value of a form element.

The summary page, the email body, and some finisher options show the
display value of a form element instead of the submitted value. For an
element with an `options` property, the display value is the translated
option label. The Gender Select element shows `Female` instead of `f`.

The example uses
:php:`\TYPO3\CMS\Form\Domain\Model\FormElements\GenericOptionableFormElement`
as `implementationClassName`. The Core elements `SingleSelect`,
`MultiSelect`, `RadioButton`, and `MultiCheckbox` use this class as
well.

See
:ref:`Display values and submitted values <concepts-finishers-customfinisherimplementations-accessingoptions-displayvalue>`
for the finisher options that use display values.

Step 2: Register the configuration
===================================

No PHP or TypoScript registration is needed. TYPO3 discovers YAML files
automatically from any extension that follows the directory convention.

Create a form set with a single :file:`config.yaml`:

..  code-block:: none

    EXT:my_extension/
      Configuration/
        Form/
          CustomElement/
            config.yaml     ← set metadata + all configuration

Set the ``priority`` in :file:`config.yaml` to a value greater than ``10``
(the EXT:form core base set) so your configuration is merged on top:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Form/CustomElement/config.yaml

    name: my-vendor/custom-element
    label: 'My Custom Form Element'
    priority: 200

    # Form element configuration goes here:
    prototypes:
      standard:
        ...

Step 3: Clear Caches
====================

After adding the configuration, you must clear all TYPO3 caches.

Step 4: Using your custom element
==================================

Now you can use your custom element in the form editor:

1. Open the Form Editor user interface (:guilabel:`Forms > [Your Form] > Edit`)
2. Look for "Gender Select" in the form element browser.
3. Add the element to the form.
4. Configure the element using the inspector panel on the right.
5. Save your form.
6. Add a form content element to a page and select the form you just edited.
7. Preview the page in the frontend.

The element will now be available in your forms and will render using the
RadioButton template in the frontend.

Step 5: Customizing frontend output (optional)
===============================================

If you want to use a custom template instead of reusing an existing one, follow
these steps:

Create custom template
----------------------

Create your own Fluid template:

:file:`EXT:my_extension/Resources/Private/Partials/Form/GenderSelect.fluid.html`

.. literalinclude:: _GenderSelect.fluid.html
   :language: html
   :caption: EXT:my_extension/Resources/Private/Partials/Form/GenderSelect.fluid.html


Update configuration
--------------------

Update your YAML configuration to use the custom partial template:

.. code-block:: yaml
   :caption: EXT:my_extension/Configuration/Form/CustomElement/config.yaml
   :emphasize-lines: 3-7, 10

   prototypes:
     standard:
       formElementsDefinition:
         Form:
            renderingOptions:
               partialRootPaths:
                  1732785721: 'EXT:my_extension/Resources/Private/Partials/Form/'
         GenderSelect:
           renderingOptions:
             templateName: 'GenderSelect'


Further reading
===============

* :ref:`Form Configuration <concepts-configuration>`
* :ref:`Register a custom stage template <apireference-formeditor-basicjavascriptconcepts-events-view-inspector-editor-insert-perform>`