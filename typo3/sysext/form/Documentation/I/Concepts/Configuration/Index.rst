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

Currently, configuration using YAML is not natively integrated into the
core of TYPO3. Because of this, YAML configuration has to be registered using TypoScript
for the frontend (for webpages) and for the backend (for the form editor).

.. hint::

   We recommend using a `site package <https://de.slideshare.net/benjaminkott/typo3-the-anatomy-of-sitepackages>`_.
   This will make your life easier if you need to do a lot of customization of EXT:form.

.. tip::

   For debugging purposes or to get an overview of the configuration
   use the :guilabel:`System > Configuration` module. Select
   the :guilabel:`Form: YAML Configuration` item in the menu to display
   parsed YAML form setup. Make sure you have the lowlevel
   system extension installed.


.. _concepts-configuration-yamlregistration-frontend:

YAML registration for the frontend
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The frontend of a form on a webpage is just a content element plugin.
The configuration YAML is loaded by configuring a
``plugin.tx_form`` TypoScript object:
(see ``EXT:form/Configuration/TypoScript/setup.typoscript``):


.. code-block:: typoscript

   plugin.tx_form {
       settings {
           yamlConfigurations {
               10 = EXT:form/Configuration/Yaml/FormSetup.yaml
           }
       }
   }

Register your own configuration with any key other than ``10``.

.. code-block:: typoscript

   plugin.tx_form {
       settings {
           yamlConfigurations {
               100 = EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml
           }
       }
   }


.. _concepts-configuration-yamlregistration-backend:
.. _concepts-configuration-yamlregistration-backend-addtyposcriptsetup:

YAML registration for the backend
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

YAML configuration is loaded in the backend (module) by TypoScript in
:file:`EXT:form/ext_localconf.php`.

..  code-block:: php
    :caption: EXT:form/ext_localconf.php

    ExtensionManagementUtility::addTypoScriptSetup('
        module.tx_form {
           settings {
               yamlConfigurations {
                   10 = EXT:form/Configuration/Yaml/FormSetup.yaml
               }
           }
        }
    ');

Register your own configuration in :file:`EXT:my_extension/ext_localconf.php`
using a unique number for the key, such as the current timestamp :

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    <?php

    declare(strict_types=1);

    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    defined('TYPO3') or die();

    ExtensionManagementUtility::addTypoScriptSetup('
        module.tx_form {
           settings {
               yamlConfigurations {
                   1732785702 = EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml
               }
           }
        }
    ');

The EXT:form backend module is registered using
the ``module.tx_form`` TypoScript object. The module and plugin are both configured
using TypoScript and are both based on
Extbase. However, the backend TypoScript needs to have "global" scope.
This is because it is not attached to a particular
page (unlike frontend plugins).
Global TypoScript is registered using the API function
:php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup()`

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

These are defined in separate files and are only loaded in the
frontend/ backend when needed. This approach has two advantages:

- clarity,
- increased performance, e.g. the ``form editor`` configuration is not
  needed in the frontend and is therefore not loaded.

It is up to you if you want to follow this guideline or if you want to put
the whole configuration into one large file.

There are some configurational aspects which cannot explicitly be assigned
to either the frontend or the backend. Instead, the configuration is
valid for both areas. For example,  frontend
configuration is necessary in the backend in order for form preview to work
correctly. When a form is rendered via the ``form plugin``,
the ``FormEngine`` configuration is needed to interpret
overridden finisher configuration.


.. _concepts-configuration-inheritances:

Inheritance
-----------

The final YAML configuration does not produce one huge file. Instead, it is
a sequential compilation process:

- Registered configuration files are parsed as YAML and
  are combined according to their order.
- The ``__inheritances`` operator is applied. It is a unique
  operator introduced by the form framework.
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

An example of overriding the basic EXT:form values is as follows. Make sure you
have registered your own configuration file with:

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

Override the EXT:form Fluid templates with your own by defining your paths in
``EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml``:

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

The values in your own configuration file ``EXT:my_site_package/Configuration/Form/CustomFormSetup.yaml`` will override the
values in the basic configuration file in EXT:Form
(:file:`EXT:form/Configuration/Yaml/FormSetup.yaml`).

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

.. _concepts-configuration-inheritances-operator:

__inheritances operator
^^^^^^^^^^^^^^^^^^^^^^^

..  deprecated:: 14.0
    The ``__inheritances`` operator has been marked as deprecated.
    Support will be removed in TYPO3 v15. Use native YAML syntax to :ref:`prevent duplication <concepts-configuration-prevent-duplication>`

The ``__inheritances`` operator is an extremely useful instrument. Using it
helps to significantly reduce the configuration effort. It behaves similar
to the ``<`` operator in TypoScript. That is, the definition of the source
object is copied to the target object. The configuration can be inherited
from several parent objects and can be overridden afterwards. Two simple
examples will show you the usage and behaviour of the ``__inheritances``
operator.

.. code-block:: yaml

   Form:
     part01:
       key01: value
       key02:
         key03: value
     part02:
       __inheritances:
         10: Form.part01

The configuration above results in:

.. code-block:: yaml

   Form:
     part01:
       key01: value
       key02:
         key03: value
     part02:
       key01: value
       key02:
         key03: value

As you can see, ``part02`` inherited all of ``part01``'s properties.

.. code-block:: yaml

   Form:
     part01:
       key: value
     part02:
       __inheritances:
         10: Form.part01
       key: 'value override'

The configuration above results in:

.. code-block:: yaml

   Form:
     part01:
       key: value
     part02:
       key: 'value override'

EXT:form heavily uses the ``__inheritances`` operator, in particular, for
the definition of form elements. The following example shows you how to use
the operator to define a new form element which behaves like the parent
element but also has its own properties.

.. code-block:: yaml

   prototypes:
     standard:
       formElementsDefinition:
         GenderSelect:
           __inheritances:
             10: 'prototypes.standard.formElementsDefinition.RadioButton'
           renderingOptions:
             templateName: 'RadioButton'
           properties:
             options:
               f: 'Female'
               m: 'Male'
               u: 'Unicorn'
               a: 'Alien'

The YAML configuration defines a new form element called ``GenderSelect``.
This element inherits its definition from the ``RadioButton`` element but
additionally ships four predefined options. Without any problems, the new
element can be used and overridden within the ``form definition``.

It will probably take some time to fully understand the awesomeness of
this operator. If you are eager to learn more about this great instrument,
check out the unit tests defined in ``EXT:form/Tests/Unit/Mvc/Configuration/InheritancesResolverServiceTest.php``.


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
