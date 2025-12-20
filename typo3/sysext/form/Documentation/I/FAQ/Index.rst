.. include:: /Includes.rst.txt


.. _faq:

===
FAQ
===


.. _faq-override-frontend-templates:

How do I override EXT:Form frontend templates?
==============================================

There are two ways to override the frontend templates.


Add fluid search paths globally
-------------------------------

EXT:form uses YAML as a configuration language so you will need to register your
own YAML files to override/add information. Let us assume you are using
sitepackage ``EXT:my_site_package`` which contains your frontend
integration.


EXT:my_site_package/Configuration/TypoScript/setup.typoscript
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

First of all, register a new YAML configuration file for the frontend
using TypoScript.

.. code-block:: typoscript

   plugin.tx_form {
       settings {
           yamlConfigurations {
               # register your own additional configuration
               # choose a number higher than 10 (10 is reserved)
               100 = EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml
           }
       }
   }


EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Next, add your sitepackage fluid template paths to your new file (CustomFormSetup.yaml above).

.. code-block:: yaml

   prototypes:
     standard:
       formElementsDefinition:
         Form:
           renderingOptions:
             templateRootPaths:
               20: 'EXT:my_site_package/Resources/Private/Templates/Form/Frontend/'
             partialRootPaths:
               20: 'EXT:my_site_package/Resources/Private/Partials/Form/Frontend/'
             layoutRootPaths:
               20: 'EXT:my_site_package/Resources/Private/Layouts/Form/Frontend/'

.. note::

   You can preview forms in the backend form editor and the preview function
   uses the frontend templates as well. If you want the preview function to show your customized
   templates, register your fluid paths in the backend module as well as the frontend as shown below.


EXT:my_site_package/ext_localconf.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Register your configuration YAML for the backend aswell by using
TypoScript in a :file:`ext_localconf.php` file. See
:ref:`chapter on configuration concepts <concepts-configuration-yamlregistration-backend>`
for more information.

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    ExtensionManagementUtility::addTypoScriptSetup('
       module.tx_form {
           settings {
               yamlConfigurations {
                   1732786693 = EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml
               }
           }
       }
    ');


.. _faq-prevent-double-submissions:

How do I prevent multiple form submissions?
===========================================

A user can submit a form twice by double-clicking the submit button. This means
finishers could be processed multiple times.

At the current time, there are no plans to integrate a function to prevent this behaviour,
especially not server side. An easy solution would be the integration of a
JavaScript function to stop the behaviour. TYPO3 itself does not take care of
any frontend integration and does not want to ship JavaScript solutions for
the frontend. Therefore, integrators have to implement a solution themselves.

One possible solution is the following JavaScript snippet. It can be added to your
site package. Please note, the selector (here :js:`myform-123`) has to be updated
to the id of your form.

.. code-block:: js

    const form = document.getElementById('myform-123');
    form.addEventListener('submit', function(e) {
        const submittedClass = 'submitted';
        if (this.classList.contains(submittedClass)) {
            e.preventDefault();
        } else {
            this.classList.add(submittedClass);
        }
    });

You could also style the submit button to provide visual feedback to the user.
This will help make it clear that the form has already been submitted and thus
prevent further interaction by the user.

.. code-block:: css

    .submitted button[type="submit"] {
        opacity: 0.6;
        pointer-events: none;
    }


.. _faq-date-picker:

How does the date picker (jQuery) work?
=======================================

EXT:form ships a datepicker form element. You will need to
add jquery JavaScript files, jqueryUi JavaScript and CSS files to
your frontend.


.. _faq-user-registration:

Is it possible to build frontend user registration with EXT:form?
=================================================================

Possible, yes. But we are not aware of an implementation.


.. _faq-export-module:

Is there an export module for saved forms?
==========================================

Currently there are no plans to implement such a feature in the core. There
are concerns regarding data privacy when it comes to storing user data in
your TYPO3 database permanently. The great folks of Pagemachine created an
`extension <https://github.com/pagemachine/typo3-formlog>`_ for this.


.. _faq-honeypt-session:

The honeypot does not work with static site caching. What can I do?
===================================================================

If you want to use static site caching - for example using the
staticfilecache extension - you should disable the automatic inclusion of the
honeypot. Read more :ref:`here<prototypes.prototypeIdentifier.formelementsdefinition.form.renderingoptions.honeypot.enable>`.


.. _faq-form-element-default-value:

How do I set a default value for my form element?
=================================================

You can set default values for most form elements (not to be confused with
the placeholder attribute). This is easy for text fields and textareas.

Select and multi-select form elements are a bit more complex. These form elements
can have :yaml:`defaultValue` and  :yaml:`prependOptionValue` settings. The
:yaml:`defaultValue` allows you to select a specific option as a default. This
option will be pre-selected when the
form is loaded. The :yaml:`prependOptionValue` defines a
string which will be the first select option. If both settings exist,
the :yaml:`defaultValue` is prioritized.

Learn more :ref:`here<prototypes.prototypeIdentifier.formelementsdefinition.formelementtypeidentifier.defaultValue>`
and see forge issue `#82422 <https://forge.typo3.org/issues/82422#note-6>`_.


.. _faq-form-element-custom-finisher:

How do I create a custom finisher for my form?
==============================================

:ref:`Learn how to create a custom finisher here.<concepts-finishers-customfinisherimplementations>`

If you want to make the finisher configurable in the backend form editor, read :ref:`here<concepts-finishers-customfinisherimplementations-extend-gui>`.


.. _faq-form-element-custom-validator:

How do I create a custom validator for my form?
===============================================

:ref:`Learn how to create a custom validator here.<concepts-validators-customvalidatorimplementations>`


.. faq-form-proposed-folder-structure:

Which folder structure do you recommend?
========================================

When shipping form configuration, form definitions,
form templates, and language files in a site package, we recommend the following
structure:

* Form configuration: :file:`EXT:my_site_package/Configuration/Form/`
* Form definitions: :file:`EXT:my_site_package/Resources/Private/Forms/`
* Form templates:
   * Templates :file:`EXT:my_site_package/Resources/Private/Templates/Form/`
   * Partials :file:`EXT:my_site_package/Resources/Private/Partials/Form/`
   * Layouts :file:`EXT:my_site_package/Resources/Private/Layouts/Form/`
   * Keep in mind that a form comes with templates for both the frontend
     (this is your website) and the TYPO3 backend. Therefore, we recommend
     splitting the templates into subfolders called :file:`Frontend/` and
     :file:`Backend/`.
* Translations: :file:`EXT:my_site_package/Resources/Private/Language/Form/`
