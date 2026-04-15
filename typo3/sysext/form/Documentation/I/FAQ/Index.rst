.. include:: /Includes.rst.txt


.. _faq:

===
FAQ
===


.. _faq-override-frontend-templates:

How do I override EXT:Form frontend templates?
==============================================

There are three ways to override the frontend templates.


Override template paths via site set settings (recommended)
-----------------------------------------------------------

The simplest approach: configure the template paths in the site settings of
your site package. The settings are applied to the Extbase plugin view and
the form element rendering.

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    form.templates.templateRootPath: EXT:my_site_package/Resources/Private/Templates/Form/Frontend/
    form.templates.partialRootPath: EXT:my_site_package/Resources/Private/Partials/Form/Frontend/
    form.templates.layoutRootPath: EXT:my_site_package/Resources/Private/Layouts/Form/Frontend/
    form.translation.translationFile: EXT:my_site_package/Resources/Private/Language/Form/locallang.xlf

Alternatively, edit the settings in the :guilabel:`Site Settings` backend
module under :guilabel:`Form Framework > Templates`.

..  note::

   Site set settings are resolved in the **frontend** only. The backend form
   editor preview uses the YAML prototype defaults. If you need the backend
   preview to use custom templates, use a form set (see below).


Add fluid search paths via a form set
-------------------------------------

Create a form set in your site package. The YAML files are picked up
automatically for **both** frontend and backend — no PHP or TypoScript
registration is required.

1.  Create the directory and a :file:`config.yaml` with the template paths:

    ..  code-block:: none

        EXT:my_site_package/
          Configuration/
            Form/
              SitePackage/
                config.yaml

    ..  code-block:: yaml
        :caption: EXT:my_site_package/Configuration/Form/SitePackage/config.yaml

        name: my-site-package/form
        label: 'My Site Package — Form Configuration'
        priority: 200

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

   Forms can be previewed in the backend form editor. The preview uses the
   same frontend templates. Your customized templates are automatically used
   in the preview as well.

See :ref:`concepts-configuration-yaml-autodiscovery` for the full directory
convention.


Add fluid search paths via TypoScript ``yamlSettingsOverrides``
---------------------------------------------------------------

For quick, per-site overrides without creating a full form set, you can use
:typoscript:`plugin.tx_form.settings.yamlSettingsOverrides`:

..  code-block:: typoscript

    plugin.tx_form.settings.yamlSettingsOverrides {
        prototypes {
            standard {
                formElementsDefinition {
                    Form {
                        renderingOptions {
                            templateRootPaths {
                                20 = EXT:my_site_package/Resources/Private/Templates/Form/Frontend/
                            }
                        }
                    }
                }
            }
        }
    }

.. note::

   TypoScript ``yamlSettingsOverrides`` are evaluated in the **frontend only**
   and are ignored by the backend form editor.


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
