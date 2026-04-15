..  include:: /Includes.rst.txt

..  _feature-109412-1742000001:

=================================================================
Feature: #109412 - Auto-discovery of form YAML configurations
=================================================================

See :issue:`109412`

Description
===========

TYPO3 Form Framework now discovers YAML configuration files automatically
from every active extension — no PHP or TypoScript registration is required.

The mechanism mirrors how :ref:`Site Sets <t3coreapi:site-sets>` work:
each extension may provide one or more *form sets* by placing files in a
conventional directory layout. TYPO3 scans all active extensions and collects
every form set it finds, powered by a Symfony service configurator
(:php:`FormYamlCollectorConfigurator`) that runs transparently when the form
service is first resolved.


Directory layout
----------------

..  code-block:: none

    EXT:my_extension/
      Configuration/
        Form/
          MyFormSet/
            config.yaml

The sub-directory name (`MyFormSet`) is arbitrary. An extension may ship
multiple sets in separate sub-directories.


The :file:`config.yaml`
-----------------------

Contains both the set metadata and the actual form configuration in a single
file. The metadata keys (`name`, `label`, `priority`) are reserved; all
other keys are treated as form configuration (prototype definitions, form
elements, validators, finishers, rendering options etc.).

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Form/MyFormSet/config.yaml

    # Unique identifier, vendor/name convention (like composer package names)
    name: my-vendor/my-form-set

    # Human-readable label for diagnostic output
    label: 'My Custom Form Set'

    # Load order: lower values are loaded first and act as base configuration.
    # Extension sets should use a value > 10 to be merged on top of the
    # TYPO3 core base set (typo3/form-base, priority: 10).
    # Default: 100
    priority: 200

    # Form configuration follows directly below the metadata:
    persistenceManager:
      allowedExtensionPaths:
        10: 'EXT:my_extension/Resources/Private/Forms/'


Priority and merge order
------------------------

Sets are sorted by ascending `priority` (lower = loaded first = acts as base,
higher = override). Each set's configuration is merged on top of the previous
one using `array_replace_recursive`, identical to how the former TypoScript
mechanism worked.


Migration from TypoScript registration
---------------------------------------

Before TYPO3 v14.2, YAML files had to be registered explicitly via TypoScript:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript

    plugin.tx_form.settings.yamlConfigurations {
        1732785702 = EXT:my_extension/Configuration/Form/MySetup.yaml
    }

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    # Backend had to be registered separately:
    module.tx_form.settings.yamlConfigurations {
        1732785703 = EXT:my_extension/Configuration/Form/MySetup.yaml
    }

This registration mechanism has been deprecated and will be removed in
TYPO3 v15.0. See :ref:`Deprecation-109412 <deprecation-109412-1742000001>`
for details.

After the migration:

1.  Create the directory :file:`EXT:my_extension/Configuration/Form/MySet/`.
2.  Create :file:`config.yaml` with `name`, optionally `priority`, and the
    form configuration directly in the same file.
3.  Remove the TypoScript registrations from :file:`setup.typoscript`.

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Form/MySet/config.yaml

    name: my-vendor/my-form-set
    label: 'My Custom Form Set'
    priority: 200

    # Form configuration (formerly your MySetup.yaml content) goes here:
    prototypes:
      standard:
        formElementsDefinition:
          ...


Disabling a form set
--------------------

Because all sets from all active extensions are loaded automatically, a
mechanism exists to opt out of specific sets without modifying the
extension that provides them.

To disable a set, add its declared `name` (from :file:`config.yaml`) to
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets']`
in :file:`ext_localconf.php` or :file:`config/system/settings.php`:

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    // Disable a third-party form set that conflicts with the site configuration:
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['form']['disabledSets'][]
        = 'some-vendor/conflicting-set';

..  note::
    Disabling the core set `typo3/form-base` will break form rendering
    entirely. Only disable it if you provide a full replacement set with
    equivalent configuration.

The matching is done against the `name` field in :file:`config.yaml`,
**not** against the directory name, so renaming the set directory does not
break an existing disable list.


EXT:form base set
-----------------

The TYPO3 Form Framework ships its own base set at
:file:`EXT:form/Configuration/Form/Base/` (`typo3/form-base`, priority 10).
All validators, form elements, finishers and shared rendering configuration
are defined directly in :file:`EXT:form/Configuration/Form/Base/config.yaml`.


Site set settings for template paths
------------------------------------

The site set `typo3/form` provides four settings to override Fluid template
paths and translation files.

..  confval:: form.templates.templateRootPath
    :type: string
    :Default: (empty)

    Override the default Fluid template path for form element rendering.

..  confval:: form.templates.partialRootPath
    :type: string
    :Default: (empty)

    Override the default Fluid partial path for form element rendering.

..  confval:: form.templates.layoutRootPath
    :type: string
    :Default: (empty)

    Override the default Fluid layout path for form element rendering.

..  confval:: form.translation.translationFile
    :type: string
    :Default: (empty)

    Add an additional XLF translation file for form element labels.

Override the template paths in the site configuration:

..  code-block:: yaml
    :caption: config/sites/my-site/settings.yaml

    form.templates.templateRootPath: EXT:my_sitepackage/Resources/Private/Templates/Form/Frontend/
    form.templates.partialRootPath: EXT:my_sitepackage/Resources/Private/Partials/Form/Frontend/
    form.templates.layoutRootPath: EXT:my_sitepackage/Resources/Private/Layouts/Form/Frontend/
    form.translation.translationFile: EXT:my_sitepackage/Resources/Private/Language/Form/locallang.xlf

Impact
======

Extensions that place a :file:`config.yaml` in
:file:`Configuration/Form/<SetName>/` will have their configuration
automatically loaded without any additional registration.

Integrators who include the `typo3/form` site set can additionally override
form template paths, partial paths, layout paths and translation files through
site settings — without touching YAML prototype configuration or TypoScript
:typoscript:`yamlSettingsOverrides`.

.. index:: YAML, Frontend, Backend, ext:form
