.. include:: /Includes.rst.txt


.. _faq:

===
FAQ
===


.. _faq-override-frontend-templates:

How do I override the frontend templates?
=========================================

There are 2 possible ways to override the frontend templates.


Globally extend the fluid search paths
--------------------------------------

Since EXT:form mainly uses YAML as configuration language you need to
register your own additional YAML files. Let us assume you are using a
sitepackage ``EXT:my_site_package`` which contains your whole frontend
integration.


EXT:my_site_package/Configuration/TypoScript/setup.typoscript
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

First of all, register a new EXT:form configuration for the frontend via
TypoScript.

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

Next, define the additional fluid template search paths via YAML.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
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

   The preview within the form editor (backend module) uses the frontend
   templates as well. If you want the preview to show your customized
   templates, register the new paths for the backend module as well.


EXT:my_site_package/ext_typoscript_setup.typoscript
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Register your EXT:form configuration for the backend via TypoScript. Read
the :ref:`chapter about configuration concepts <concepts-configuration-yamlregistration-backend>`
to learn about the recommended ways.

.. code-block:: typoscript

   module.tx_form {
       settings {
           yamlConfigurations {
               100 = EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml
           }
       }
   }


.. _faq-prevent-double-submissions:

How do I disable multiple form submissions?
===========================================

The use case is quite obvious: a user can submit a form twice by double
clicking the submit button. This could cause trouble since the attached finishers
are processed multiple times.

Right now, there are no plans to integrate a feature to prevent this behaviour,
especially not server side. An easy solution could be the integration of a
JavaScript function to stop the behaviour. TYPO3 itself does not take care of
any frontend integration and does not want to ship JavaScript solutions for
the frontend. Therefore, integrators have to take care and implement a solution.

As an example, check out the following JavaScript snippet. This should do the
trick. It can be added to the site package of the TYPO3 installation. Please
note, the selector (here :js:`myform-123`) has to be adapted to the id of the
corresponding form.

.. code-block:: js

   document.getElementById('myform-123')
       .addEventListener('submit', function(e) {
           e.target.querySelectorAll('[type="submit"]')
               .forEach(function(button) {
                   button.disabled = true;
               });
       });


.. _faq-migrate-from-v7:

How do I migrate from EXT:form v7?
==================================

The old form extension (used in TYPO3 v7, which is compatible to TYPO3 v6)
was moved into an own extension called ``form_legacy``.  This extension can
be found within the official `TER <https://typo3.org/extensions/repository/view/form_legacy>`_.
When upgrading to TYPO3 v8 an upgrade wizard will tell you if form_legacy is
still needed.


.. _faq-date-picker:

How does the date picker (jQuery) work?
=======================================

EXT:form ships a datepicker form element. To unfold its full potential you
should add jquery JavaScript files and jqueryUi JavaScript and CSS files to
your frontend.


.. _faq-user-registration:

Is it possible to build a frontend user registration with EXT:form?
===================================================================

Possible, yes. But we are not aware of an integration.


.. _faq-export-module:

Is there some kind of export module for saved forms?
====================================================

Currently, there are no plans to implement such a feature into the core. There
are concerns regarding the data privacy when it comes to storing user data in
your TYPO3 database permanently. The great folks of Pagemachine created an
`extension <https://github.com/pagemachine/typo3-formlog>`_ for this behalf.


.. _faq-honeypt-session:

The honeypot does not work with static site caching. What can I do?
===================================================================

If you want to use a static site caching - for example using the
staticfilecache extension - you should disable the automatic inclusion of the
honeypot. Read more :ref:`here<typo3.cms.form.prototypes.\<prototypeIdentifier>.formelementsdefinition.form.renderingoptions.honeypot.enable>`.


.. _faq-form-element-default-value:

How do I set a default value for my form element?
=================================================

Most of the form elements support setting a default value (do not mix this
up with the placeholder attribute). For a text field or a textarea, this is
quite trivial.

A little bit more thrilling is the handling for select and multi select form
elements. Those special elements support - beside the :yaml:`defaultValue` - a
:yaml:`prependOptionValue` setting. The :yaml:`defaultValue` allows you to select a
specific option as default. This option will be pre-selected as soon as the
form is loaded. In contrast, the :yaml:`prependOptionValue` allows you to define a
string which will be shown as the first select-option. If both settings exist,
the :yaml:`defaultValue` is prioritized.

Learn more :ref:`here<typo3.cms.form.prototypes.\<prototypeIdentifier>.formelementsdefinition.\<formelementtypeidentifier>.defaultValue>`
and see the forge issue `#82422 <https://forge.typo3.org/issues/82422#note-6>`_.


.. _faq-form-element-custom-finisher:

How do I create a custom finisher for my form?
==============================================

:ref:`Learn how to create a custom finisher here.<concepts-finishers-customfinisherimplementations>`

If you want to make the finisher configurable in the backend UI read :ref:`here<concepts-finishers-customfinisherimplementations-extend-gui>`.


.. _faq-form-element-custom-validator:

How do I create a custom validator for my form?
===============================================

:ref:`Learn how to create a custom validator here.<concepts-validators-customvalidatorimplementations>`


.. faq-form-proposed-folder-structure:

Which folder structure do you recommend?
========================================

When shipping a custom form configuration, form definitions, differing
form templates, or language files you may wonder how the perfect folder
structure within your site package could look like.
We recommend the following structure:

* Custom form configuration: :file:`EXT:my_site_package/Configuration/Form/`
* Form definitions: :file:`EXT:my_site_package/Resources/Private/Forms/`
* Custom form templates:
   * Templates :file:`EXT:my_site_package/Resources/Private/Templates/Form/`
   * Partials :file:`EXT:my_site_package/Resources/Private/Partials/Form/`
   * Layouts :file:`EXT:my_site_package/Resources/Private/Layouts/Form/`
   * Keep in mind that form comes with templates for both the frontend
     (this is your website) and the TYPO3 backend. Therefore, we recommend
     splitting the templates in subfolders called :file:`Frontend/` and
     :file:`Backend/`.
* Translations: :file:`EXT:my_site_package/Resources/Private/Language/Form/`
