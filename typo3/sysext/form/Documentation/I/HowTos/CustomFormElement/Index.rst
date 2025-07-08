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

Step 1: Create the configuration file
=====================================

First, create a YAML configuration file for your custom form element. This file
defines how the element behaves in both form editor and frontend.

File location
-------------

Create the following file in your extension (also create the directories if they do not yet exist):

:file:`EXT:my_extension/Configuration/Form/CustomFormSetup.yaml`

Configuration Structure
-----------------------

Here's the complete configuration for our Gender Select element:

.. literalinclude:: _CustomFormSetup.yaml
   :language: yaml
   :caption: EXT:my_extension/Configuration/Form/CustomFormSetup.yaml

Common inspector editors
~~~~~~~~~~~~~~~~~~~~~~~~

Here are some commonly used inspector editors (:ref:`Inspector <concepts-formeditor-inspector>`) you can add to your form elements:

**Inspector-FormElementHeaderEditor** (100)
   Shows the element header in the inspector panel

**Inspector-TextEditor** (200-300)
   A simple text input field for properties like label and description

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

Step 2: Register the configuration
===================================

The YAML configuration must be registered in two places to work in both the form
editor (backend) and the frontend.

Backend registration (Form Editor)
----------------------------------

Register your YAML configuration file in your extension's
:file:`ext_localconf.php`:

.. literalinclude:: _ext_localconf.php
   :language: php
   :caption: EXT:my_extension/ext_localconf.php

.. important::

   The numeric key (``1732785702`` in this example) should be unique across all
   configurations. You can use a timestamp to ensure uniqueness. This number
   determines the loading order - higher numbers are loaded later and can override
   earlier settings.

Frontend registration (TypoScript)
-----------------------------------

To render the custom form element in the frontend, you must also register the
YAML configuration via TypoScript. Add the following to your site's TypoScript
setup (e.g., in :file:`EXT:my_extension/Configuration/TypoScript/setup.typoscript`):

.. code-block:: typoscript
   :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript

   plugin.tx_form {
       settings {
           yamlConfigurations {
               1732785702 = EXT:my_extension/Configuration/Form/CustomFormSetup.yaml
           }
       }
   }

.. note::

   Please make sure your TypoScript template is included in the site configuration.

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
   :caption: EXT:my_extension/Configuration/Form/CustomFormSetup.yaml
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