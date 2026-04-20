..  include:: /Includes.rst.txt

..  _deprecation-109412-1742000001:

==============================================================
Deprecation: #109412 - TypoScript-based form YAML registration
==============================================================

See :issue:`109412`

Description
===========

The TypoScript-based registration of form YAML configuration files via
:typoscript:`plugin.tx_form.settings.yamlConfigurations` and
:typoscript:`module.tx_form.settings.yamlConfigurations` has been
deprecated in favor of the new auto-discovery mechanism introduced in
TYPO3 v14.2 (see :ref:`Feature-109412 <feature-109412-1742000001>`).

Before TYPO3 v14.2 this was the only way to register `EXT:form` YAML files. It
required separate registration of the frontend and the backend
in TypoScript:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript — deprecated

    plugin.tx_form.settings.yamlConfigurations {
        1732785702 = EXT:my_extension/Configuration/Form/MySetup.yaml
    }

    # Backend had to be registered separately:
    module.tx_form.settings.yamlConfigurations {
        1732785703 = EXT:my_extension/Configuration/Form/MySetup.yaml
    }

The TypoScript-based paths will still be loaded during the deprecation
period but will be removed in TYPO3 v15.0.

Impact
======

Extensions that register form YAML files via TypoScript will trigger a
PHP :php:`E_USER_DEPRECATED` error. The registered YAML files are still
loaded and will remain functional during the deprecation period.

Affected installations
======================

All installations where an extension registers form YAML files via:

*   :typoscript:`plugin.tx_form.settings.yamlConfigurations`
*   :typoscript:`module.tx_form.settings.yamlConfigurations`

Migration
=========

Replace TypoScript registration with the auto-discovery directory
convention introduced in TYPO3 v14.2
(see :ref:`Feature-109412 <feature-109412-1742000001>`).

1.  Create directory :file:`EXT:my_extension/Configuration/Form/MySet/`.

2.  Add a :file:`config.yaml` file with a unique `name` and, optionally,
    a `priority` value (default: 100; the core base set is
    priority 10):

    ..  code-block:: yaml
        :caption: EXT:my_extension/Configuration/Form/MySet/config.yaml

        name: my-vendor/my-form-set
        label: 'My Custom Form Set'
        priority: 200

3.  Add your existing form configuration to :file:`config.yaml` below
    the metadata keys:

    ..  code-block:: yaml
        :caption: EXT:my_extension/Configuration/Form/MySet/config.yaml

        name: my-vendor/my-form-set
        label: 'My Custom Form Set'
        priority: 200

        # Content of your former MySetup.yaml
        persistenceManager:
          allowedExtensionPaths:
            10: 'EXT:my_extension/Resources/Private/Forms/'

4.  Remove TypoScript registrations from
    :file:`setup.typoscript`. PHP or TypoScript registration is
    no longer necessary.

The YAML files are picked up automatically for **both** frontend and
backend without any additional registration.

..  index:: YAML, Frontend, Backend, FullyScanned, ext:form
