.. include:: ../../Includes.txt


.. _concepts-configuration:

Configuration
=============


.. _concepts-configuration-whysomuchconfiguration:

A lot of configuration. Why?
----------------------------

The requirements for building forms in a declarative and programmatic way
are complex. What we have learned so far is that the program code must be
kept as generic as possible to handle the dynamics of forms, but a generic
program code means a lot of configurative overhead.

Initially, the configuration may overwhelm you, but it also has some great
advantages. Many aspects of EXT:form can be manipulated in a purely
configurative manner without involving a developer.

Furthermore, we wanted to avoid the configuration being done at places
whose context actually suggests something different. This pedantry,
however, leads to the situation in which certain settings have to be
defined multiple times at multiple places. This may seem nonsensical, but
it avoids unpredictable behaviour. Within the form framework, nothing
happens magically. It is all about configuration.


.. _concepts-configuration-whyyaml:

Why YAML?
---------

Former versions of EXT:form used a subset of TypoScript to describe the
definition of a specific form and the behaviour of the included form
elements. This led to a lot of confusion from integrators because the
implemented definition language looked like TypoScript but did not behave
like TypoScript.

Since the definition of forms and form elements must be declarative, the
EXT:form team decided to use YAML. Just through the visual appearance of
YAML, it should be clear to everyone that neither magic nor TypoScript
stdWrap functionality are possible.


.. _concepts-configuration-yamlregistration:

YAML registration
-----------------

At the moment, configuration via YAML is not natively integrated into the
core of TYPO3. You have to make a short detour by using TypoScript in order
to register your YAML configuration. Furthermore, there is a "speciality"
regarding the integration of your YAML configuration for the backend
module.

.. hint::

   We recommend using a `site package <https://de.slideshare.net/benjaminkott/typo3-the-anatomy-of-sitepackages>`_.
   This will make your life easier if you want to customise EXT:form
   heavily in order to suit the customer's needs.


.. _concepts-configuration-yamlregistration-frontend:

YAML registration for the frontend
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

EXT:form registers two YAML configuration files which are required for the
frontend.

.. code-block:: typoscript

   plugin.tx_form {
       settings {
           yamlConfigurations {
               10 = EXT:form/Configuration/Yaml/BaseSetup.yaml
               20 = EXT:form/Configuration/Yaml/FormEngineSetup.yaml
           }
       }
   }

Since the keys 10 and 20 are already taken, we recommend registering your
own configuration beginning with the key ``100``.

.. code-block:: typoscript

   plugin.tx_form {
       settings {
           yamlConfigurations {
               100 = EXT:my_site_package/Configuration/Yaml/CustomFormSetup.yaml
           }
       }
   }

.. _concepts-configuration-yamlregistration-backend:

YAML registration for the backend
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

EXT:form registers three YAML configuration files which are required for
the backend.

.. code-block:: typoscript

   module.tx_form {
       settings {
           yamlConfigurations {
               10 = EXT:form/Configuration/Yaml/BaseSetup.yaml
               20 = EXT:form/Configuration/Yaml/FormEditorSetup.yaml
               30 = EXT:form/Configuration/Yaml/FormEngineSetup.yaml
           }
       }
   }

Since the keys 10, 20, and 30 are already taken, we recommend registering
your own configuration beginning with the key ``100``.

.. code-block:: typoscript

   module.tx_form {
       settings {
           yamlConfigurations {
               100 = EXT:my_site_package/Configuration/Yaml/CustomFormSetup.yaml
           }
       }
   }

.. important::
   Consider the following methods to register TypoScript for the backend.

The backend module of EXT:form is based on Extbase. Such backend modules
can, like frontend plugins, be configured via TypoScript. The frontend
plugins are configured below ``plugin.tx_[pluginkey]``. For the
configuration of the backend ``module.tx_[pluginkey]`` is used.

There are different ways to include the TypoScript configuration for the
backend:

- a) use the API function ``\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup()``,
- b) add the configuration to your existing TypoScript template.

In both cases, the ``form editor`` will work as expected regardless the
chosen page from the page tree. If using the aforementioned method b, the
configuration would only be valid on a specific page tree, unless you add
your configuration to all trees within your installation. Nevertheless,
being on the root page (uid 0) would still be a problem.

To sum it up: choose either method a or b, and you will be fine.

.. _concepts-configuration-yamlregistration-backend-addtyposcriptsetup:

YAML registration for the backend via addTypoScriptSetup()
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Add the following PHP code to your :file:`ext_localconf.php` of your site
package::

   defined('TYPO3_MODE') or die();

   call_user_func(function () {
       if (TYPO3_MODE === 'BE') {
           \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
               trim('
                   module.tx_form {
                       settings {
                           yamlConfigurations {
                               100 = EXT:my_site_package/Configuration/Yaml/CustomFormSetup.yaml
                           }
                       }
                   }
               ')
           );
       }
   });


.. _concepts-configuration-configurationaspects:

Configuration aspects
---------------------

In EXT:form, four aspects can be configured:

- the behaviour of the frontend rendering,
- the behaviour of the ``form editor``,
- the behaviour of the ``form manager``, and
- the behaviour of the ``form plugin``.

Those aspects are defined in separate files which are only loaded in the
frontend/ backend when needed. This approach has two advantages:

- increased clarity,
- increased performance, e.g. the ``form editor`` configuration is not
  needed in the frontend and therefore not loaded.

It is up to you if you want to follow this guideline or if you want to put
the whole configuration into one large file.

There are some configurational aspects which cannot explicitly be assigned
to either the frontend or the backend. Instead, the configuration is
valid for both areas. For example, within the backend, the whole frontend
configuration is required in order to allow the form preview to work
properly. In addition, as soon as the form is rendered via the ``form
plugin``, the ``FormEngine`` configuration is needed to interpret the
overridden finisher configuration correctly.


.. _concepts-configuration-inheritances:

Inheritances
------------

The final YAML configuration is not based on one huge file. Instead, it is
a compilation of a sequential process:

- First of all, all registered configuration files are parsed as YAML and
  are overlaid according to their order.
- After that, the ``__inheritances`` operator is applied. It is a unique
  operator introduced by the form framework.
- Finally, all configuration entries with a value of ``null`` are deleted.

Additionally, the frontend configuration can be extended/ overridden by
TypoScript:

.. code-block:: typoscript

   plugin.tx_form {
       settings {
           yamlSettingsOverrides {
               ...
           }
       }
   }

.. note::

   Your TypoScript overrides are not interpreted by the ``form editor``,
   i.e. those settings are ignored.

.. note::

   The described process is quite handy for you. As soon as you are working
   with your :ref:`own configuration files <concepts-configuration-yamlregistration>`,
   you only have to define the differences compared to the previously
   loaded configuration files.

For example, if you want to override the fluid templates and you therefore
register an additional configuration file via

.. code-block:: typoscript

   plugin.tx_form {
       settings {
           yamlConfigurations {
               # register your own additional configuration
               # choose a number higher than 30 (below is reserved)
               100 = EXT:my_site_package/Configuration/Yaml/CustomFormSetup.yaml
           }
       }
   }

... you only have to define the following YAML setup in ``EXT:my_site_package/Configuration/Yaml/CustomFormSetup.yaml``:

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
                     20: 'EXT:my_site_package/Resources/Private/Frontend/Templates/'
                   partialRootPaths:
                     20: 'EXT:my_site_package/Resources/Private/Frontend/Partials/'
                   layoutRootPaths:
                     20: 'EXT:my_site_package/Resources/Private/Frontend/Layouts/'

The values of your own configuration file will overrule the corresponding
values of the basic configuration file (:file:`EXT:form/Configuration/Yaml/BaseSetup.yaml`).


.. _concepts-configuration-inheritances-operator:

__inheritances operator
^^^^^^^^^^^^^^^^^^^^^^^

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

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               GenderSelect:
                 __inheritances:
                   10: 'TYPO3.CMS.Form.prototypes.standard.formElementsDefinition.RadioButton'
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

.. hint::

   Currently, there is no built-in solution within the TYPO3 core to
   preview the resulting/ final EXT:form YAML configuration. If you want
   to check the configuration, there is a fishy way which you should never
   implement on a production system.

   Open the file ``typo3/sysext/form/Classes/Mvc/Configuration/ConfigurationManager.php::getConfigurationFromYamlFile()``
   and add the following code before the ``return`` statement::

      \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($yamlSettings, 'form configuration', 9999);

   Now open the ``Forms`` module in the backend or navigate to a page in
   the frontend which contains a form. The DebuggerUtility will print the
   final configuration directly to the screen.

It will probably take some time to fully understand the awesomeness of
this operator. If you are eager to learn more about this great instrument,
check out the unit tests defined in ``EXT:form/Tests/Unit/Mvc/Configuration/InheritancesResolverServiceTest.php``.


.. _concepts-configuration-prototypes:

Prototypes
----------

Most of the configurational aspects of the form framework are defined
in so-called ``prototypes``. By default, EXT:form defines a prototype
named ``standard``. The definition of form elements - including their
rendering in the frontend, ``form editor`` and ``form plugin`` - reside
within those prototypes. As soon as you create a new form, the specific
form definition references such a prototype.

This allows you to do a lot of nifty stuff. Let your imagination run free.
For example:

- based on the referenced prototype, the same form can load

  - ...varying templates
  - ...varying ``form editor`` configurations
  - ...varying ``form plugin`` finisher overrides

- within the ``form manager``, depending on the selected prototype

  - ...varying ``form editor`` configurations can be loaded
  - ...varying pre-configured form templates (boilerplates) can be chosen

- different prototypes can define different/ extended form elements and
  display them in the frontend/ ``form editor`` accordingly

Check out the following use case to fully understand the concept behind
prototypes. Imagine that there are two defined prototypes: "noob" and
"poweruser".

.. t3-field-list-table::
 :header-rows: 1

 - :a:
   :b: Prototype "noob"
   :c: Prototype "poweruser"

 - :a: **Available form elements within the ``form editor``**
   :b: Text, Textarea
   :c: No changes. Default behaviour.

 - :a: **Available finisher within the ``form editor``**
   :b: Only the email finisher is available. It offers a field for setting
       the subject of the mail. All remaining fields are hidden and filled
       with default values.
   :c: No changes. Default behaviour.

 - :a: **Finisher overrides within the ``form plugin``**
   :b: It is not possible to override the finisher configuration.
   :c: No changes. Default behaviour.
