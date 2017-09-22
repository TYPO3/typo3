.. include:: ../../Includes.txt

===================================================================
Deprecation: #82089 - EXT:form yamlConfigurations TypoScript option
===================================================================

See :issue:`82089`
See Feature-82089-ExtFormSupportsYamlImports.rst

Description
===========

The registration of YAML configuration paths for the `form` extension
via :typoscript:`<module|plugin>.tx_form.settings.yamlConfigurations`
is deprecated and will be removed in TYPO3 v10.

Instead, a single configuration file must be registered via
:typoscript:`<module|plugin>.tx_form.settings.configurationFile`.


Impact
======

If paths are added to the TypoScript option
:typoscript:`<module|plugin>.tx_form.settings.yamlConfigurations`
a deprecation log entry will be triggered.


Affected Installations
======================

All installations that add paths to the TypoScript option
:typoscript:`<module|plugin>.tx_form.settings.yamlConfigurations`.


Migration
=========

All occurrences of :typoscript:`<module|plugin>.tx_form.settings.yamlConfigurations`
must be migrated to :typoscript:`plugin.tx_form.settings.configurationFile`.


Form frontend setup
-------------------

The registration of custom frontend `form` configuration was previously done like this:

.. code-block:: typoscript

   plugin.tx_form {
       settings {
           yamlConfigurations {
               100 = EXT:my_site_package/Configuration/Yaml/CustomFormSetup.yaml
           }
       }
   }

This must be changed to use the new :typoscript:`plugin.tx_form.settings.configurationFile` option:

.. code-block:: typoscript

   plugin.tx_form {
       settings {
           configurationFile = EXT:my_site_package/Configuration/Yaml/CustomFormSetup.yaml
       }
   }

:file:`EXT:my_site_package/Configuration/Yaml/CustomFormSetup.yaml` should look like this:

.. code-block:: yaml

   imports:
     - { resource: "EXT:form/Configuration/Yaml/FormSetup.yaml" }

   # Custom form setup configuration


Form backend setup (form editor)
--------------------------------

The registration for custom backend `form` editor configuration was previously done like this:

.. code-block:: typoscript

   module.tx_form {
       settings {
           yamlConfigurations {
               100 = EXT:my_site_package/Configuration/Yaml/CustomFormEditorSetup.yaml
           }
       }
   }

This must be changed to use the new :typoscript:`module.tx_form.settings.configurationFile` option:

.. code-block:: typoscript

   module.tx_form {
       settings {
           configurationFile = EXT:my_site_package/Configuration/Yaml/CustomFormEditorSetup.yaml
       }
   }

:file:`EXT:my_site_package/Configuration/Yaml/CustomFormEditorSetup.yaml` should look like this:

.. code-block:: yaml

   imports:
     - { resource: "EXT:form/Configuration/Yaml/FormSetup.yaml" }

   # Custom form editor setup configuration

.. index:: ext:form, Frontend, Backend, NotScanned