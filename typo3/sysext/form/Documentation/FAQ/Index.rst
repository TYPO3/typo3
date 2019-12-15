.. include:: ../Includes.txt


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
               # choose a number higher than 30 (below is reserved)
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


EXT:my_site_package/ext_localconf.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

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


.. _faq-migrate-from-v7:

How do I migrate from EXT:form v7?
==================================

The old form extension (used in TYPO3 v7, which is compatible to TYPO3 v6)
was moved into an own extension called ``form_legacy``.  This extension can
be found within the official `TER <https://typo3.org/extensions/repository/view/form_legacy>`_.
When upgrading to TYPO3 v8 an upgrade wizard will tell you if form_legacy is
still needed.


.. _faq-date-picker:

How does the date picker work?
==============================

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

Currently, there are no plans to implement such a feature. There are
concerns regarding the data privacy when it comes to storing user data in
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

* Custom form configuration: ``EXT:my_site_package/Configuration/Form/``
* Form definitions: ``EXT:my_site_package/Resources/Private/Forms/``
* Custom form templates:
   * Templates ``EXT:my_site_package/Resources/Private/Templates/Form/``
   * Partials ``EXT:my_site_package/Resources/Private/Partials/Form/``
   * Layouts ``EXT:my_site_package/Resources/Private/Layouts/Form/``
   * Keep in mind that form comes with templates for both the frontend
     (this is your website) and the TYPO3 backend. Therefore, we recommend
     splitting the templates in subfolders called ``Frontend/`` and
     ``Backend/``.
* Translations: ``EXT:my_site_package/Resources/Private/Language/Form/``
