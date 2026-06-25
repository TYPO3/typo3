.. include:: /Includes.rst.txt


.. _concepts-configuration:

Configuration
=============


.. _concepts-configuration-whysomuchconfiguration:

A lot of configuration. Why?
----------------------------

Building forms in a declarative and programmatic way is complex. Dynamic forms need
program code that is as generic as possible. But generic
program code means a lot of configurative overhead.

Having so much configuration may seem overwhelming, but it has a lot of
advantages. Many aspects of EXT:form can be manipulated purely
by configuration and without having to involve a developer.

The configuration in EXT:form is mainly located in places which make sense to a
user. However, this means that certain settings have to be
defined in multiple places in order to avoid unpredictable behaviour. There is
no magic in the form framework - it is all about configuration.


.. _concepts-configuration-whyyaml:

Why YAML?
---------

Previous versions of EXT:form used a subset of TypoScript to describe form definitions and
form element behavior. This led to a lot of confusion among integrators because the
definition language looked like TypoScript but did not behave
like TypoScript.

Form and form element definitions had to be declarative, so YAML was chosen as it is
a declarative language.

.. _concepts-configuration-yamlregistration:

YAML registration
-----------------

YAML configuration files are discovered automatically — no PHP or TypoScript
registration is required.

Place your YAML files in :file:`EXT:my_extension/Configuration/Form/<SetName>/` and
add a :file:`config.yaml` with a unique set name. TYPO3 scans all active
extensions and loads the files automatically for both frontend and backend.

.. tip::

   For debugging purposes or to get an overview of the configuration
   use the :guilabel:`System > Configuration` module. Select
   the :guilabel:`Form: YAML Configuration` item in the menu to display
   parsed YAML form setup. Make sure you have the lowlevel
   system extension installed.

.. tip::

   We recommend using a `site package <https://de.slideshare.net/benjaminkott/typo3-the-anatomy-of-sitepackages>`_.
   This will make your life easier if you need to do a lot of customization of EXT:form.


.. _concepts-configuration-yaml-autodiscovery:
.. _concepts-configuration-yamlregistration-frontend:
.. _concepts-configuration-yamlregistration-backend:
.. _concepts-configuration-yamlregistration-backend-addtyposcriptsetup:

Auto-discovery directory convention
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

..  code-block:: none

    EXT:my_extension/
      Configuration/
        Form/
          MyFormSet/
            config.yaml

The sub-directory name (``MyFormSet``) is arbitrary. An extension may ship
multiple sets in separate sub-directories.

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Form/MyFormSet/config.yaml

    name: my-vendor/my-form-set
    label: 'My Custom Form Set'
    # Load order: lower = loaded first. Core base set uses priority 10.
    # Extension sets should use > 10 (default: 100) to overlay the base.
    priority: 200

    # Form configuration goes directly below the metadata:
    persistenceManager:
      allowedExtensionPaths:
        10: 'EXT:my_extension/Resources/Private/Forms/'


.. _concepts-configuration-yamlloading:

YAML loading
------------

TYPO3 uses a ':ref:`YAML loader<t3coreapi:yamlFileLoader>`' for handling
YAML, based on the Symfony YAML package. This YAML loader is able to resolve
environment variables. In addition, EXT:form comes with its own YAML loader, but it
has some restrictions, especially when resolving environment
variables. This is for security reasons.

EXT:form differentiates between :ref:`form configuration and form definition<concepts-formdefinition-vs-formconfiguration>`.
A form definition can be :ref:`stored<concepts-form-file-storages>`
in the file system (FAL) or can be shipped with an extension. The type of YAML loader
used depends on the setup.

.. t3-field-list-table::
 :header-rows: 1

 - :a: YAML file
   :b: YAML loader

 - :a: YAML configuration
   :b: TYPO3 core

 - :a: YAML definition stored in file system (default when using the ``form editor``)
   :b: TYPO3 Form Framework

 - :a: YAML definition stored in an extension
   :b: TYPO3 core


.. _concepts-configuration-configurationaspects:

Configuration aspects
---------------------

Four things can be configured in EXT:form:

- frontend rendering,
- the ``form editor``,
- the ``form manager``, and
- the ``form plugin``.

All configuration is placed in a single :file:`config.yaml` per form set and
is loaded for both frontend and backend. It is up to you whether you want to
keep all configuration in one set or spread it across multiple form sets with
different priorities.


.. _concepts-configuration-inheritances:

Inheritance
-----------

The final YAML configuration does not produce one huge file. Instead, it is
a sequential compilation process:

- Registered configuration files are parsed as YAML and
  are combined according to their order.
- Finally, all configuration entries with a value of ``null`` are deleted.

Instead of inheritance, you can also extend/override the frontend configuration
using TypoScript:

.. code-block:: typoscript

   plugin.tx_form {
       settings {
           yamlSettingsOverrides {
               ...
           }
       }
   }

.. note::

   TypoScript overrides like this are ignored by the backend ``form editor``.

.. note::

   This process makes life easier. If you are working
   with your :ref:`own configuration files <concepts-configuration-yamlregistration>`,
   you only have to define things that are different to what was in the previously
   loaded configuration files.

An example of overriding the EXT:form Fluid templates. Place the configuration
in :file:`EXT:my_site_package/Configuration/Form/SitePackage/config.yaml`
(auto-discovered, no PHP or TypoScript registration required):

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

The values in your own configuration file will be merged on top of the EXT:form
base set (:file:`EXT:form/Configuration/Form/Base/config.yaml`).

.. _concepts-configuration-prevent-duplication:

Prevent duplication
^^^^^^^^^^^^^^^^^^^

You can avoid duplication in your YAML files by using anchors (&), aliases (*) and overrides (<<:).

..  code-block:: yaml

    customEditor: &customEditor
      1761226183:
        identifier: custom
        templateName: Inspector-TextEditor
        label: Custom editor
        propertyPath: custom

    otherCustomEditor: &otherCustomEditor
      identifier: otherCustom
      templateName: Inspector-TextEditor
      label: Other custom editor
      propertyPath: otherCustom

    prototypes:
      standard:
        formElementsDefinition:
          Text:
            formEditor:
              editors:
                <<: *customEditor
                1761226184: *otherCustomEditor


.. _concepts-configuration-placeholders:

Referencing values with placeholders
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In addition to anchors and aliases, the TYPO3 YAML loader supports ``%...%``
placeholders. Unlike anchors, they work *across* imported files, because they
are resolved *after* all files have been parsed and merged.

A placeholder is a dot-separated path into the merged configuration. The
referenced value is looked up and substituted:

``%path.to.value%``

How the result is inserted depends on where the placeholder is used:

*   **Whole value** – if the placeholder is the *only* content of a value, it is
    replaced by the referenced value as-is. This may be a scalar **or a complete
    array/subtree**.

*   **Inside a string** – if the placeholder is embedded in a larger string, the
    referenced value must be scalar (string or numeric) and is interpolated.

Placeholders can be nested and are resolved recursively. If a referenced path
does not exist, the placeholder is left unchanged.

Reusing a single value from an existing form element works the same way – here
the new ``CustomText`` element takes over the label of the core ``Text``
element:

..  code-block:: yaml

    prototypes:
      standard:
        formElementsDefinition:
          CustomText:
            formEditor:
              label: '%prototypes.standard.formElementsDefinition.Text.formEditor.label%'


.. _concepts-configuration-inherit-across-files:

Inheriting a complete element across files
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Because a whole-value placeholder substitutes a complete subtree, it can be used 
to base a new form element on the complete configuration of an existing (core) element and
override only a few properties.

A placeholder is resolved *after* parsing and replaces a whole value. The
inheritance and the overrides therefore live in two files: the imported file
copies the complete element subtree, the importing file merges its overrides on
top.

Imported file, copies the whole ``Text`` element to ``CustomText``:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Form/CustomElement/CustomTextInherit.yaml

    imports:
      - { resource: 'EXT:form/Configuration/Form/Base/FormElements/Text.yaml' }

    prototypes:
      standard:
        formElementsDefinition:
          CustomText: '%prototypes.standard.formElementsDefinition.Text%'

Importing file, overrides only single properties:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Form/CustomElement/config.yaml

    imports:
      - { resource: 'EXT:my_extension/Configuration/Form/CustomElement/CustomTextInherit.yaml' }

    prototypes:
      standard:
        formElementsDefinition:
          CustomText:
            formEditor:
              label: 'Custom Text'
              group: custom
              iconIdentifier: form-text

``CustomText`` now inherits the complete configuration of the core ``Text``
element, while only the listed properties are overridden.


.. _concepts-configuration-prototypes:

Prototypes
----------

Most of the form framework configuration is defined
in ``prototypes``. ``standard`` is the default prototype in EXT:form. Prototypes
contain form element definitions - including frontend rendering, ``form editor``
and ``form plugin``. When you create a new form, your form *definition* references
a prototype *configuration*.

This allows you to do a lot of clever stuff. For example:

- depending on which prototype is referenced, the same form can load different

  - ...templates
  - ...``form editor`` configurations
  - ...``form plugin`` finisher overrides

- in the ``form manager``, depending on the selected prototype

  - ...different ``form editor`` configurations can be loaded
  - ...different pre-configured form templates (boilerplates) can be chosen

- prototypes can define different/ extended form elements and
  display them in the frontend/ ``form editor``

The following use case illustrates the prototype concept. Imagine that two
prototypes are defined: "noob" and
"poweruser".

.. t3-field-list-table::
 :header-rows: 1

 - :a:
   :b: Prototype "noob"
   :c: Prototype "poweruser"

 - :a: **Form elements in the ``form editor``**
   :b: Just Text, Textarea
   :c: No changes. Default behaviour.

 - :a: **Finisher in the ``form editor``**
   :b: Only the email finisher is available. It has a field for setting
       the subject of the email. The rest of the fields are hidden and filled
       with default values.
   :c: No changes. Default behaviour.

 - :a: **Finisher overrides in the ``form plugin``**
   :b: It is not possible to override the finisher configuration.
   :c: No changes. Default behaviour.
