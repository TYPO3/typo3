:navigation-title: Configuration

..  include:: /Includes.rst.txt
..  _module-configuration:

=============================
Module System > Configuration
=============================

The Configuration module allows integrators to view and validate the global
configuration of TYPO3. The module displays all relevant global variables such
as TYPO3_CONF_VARS, TCA and many more, in a tree format which is easy to browse
through. Over time this module got extended to also display the configuration of
newly introduced features like the middleware stack or the event listeners.

..  contents:: Table of contents

..  _module-configuration-usage:

Using the Configuration module to view global variables
=======================================================

.. include:: /Images/AutomaticScreenshots/Modules/Configuration.rst.txt

.. rst-class:: bignums-attention

1.  Access this module in the TYPO3 backend under
    :guilabel:`System > Configuration`.

2.  Select the desired configuration entry in the upper menu bar.

3.  To find a configuration setting quickly enter a phrase in the search box.

    Is is also possible to use a regular expression for the search phrase.
    Click on the dropdown box and enable the :guilabel:`Use regular expression`
    checkbox.

4.  The configuration tree of the selected entry is displayed.

    Expand and collapse the settings with clicking on the triangle.

The Configuration module displays various configuration settings:

- :ref:`Global configuration <t3coreapi:typo3ConfVars>` (:php:`$GLOBALS['TYPO3_CONF_VARS']`)
- :doc:`Table configuration array <t3tca:Index>` (:php:`$GLOBALS['TCA']`)
- :ref:`Registered services <t3coreapi:services>` (:php:`$GLOBALS['T3_SERVICES']`)
- :ref:`User settings configuration <t3coreapi:user-settings>` (:php:`$GLOBALS['TYPO3_USER_SETTINGS']`)
- :ref:`Table permissions by page type <t3coreapi:page-types-intro>`
- :ref:`User settings <t3coreapi:be-user-configuration>` (:php:`$GLOBALS['BE_USER']->uc`)
- :ref:`User TSconfig <t3tsref:usertsconfig>` (:php:`$GLOBALS['BE_USER']->getTSConfig()`)
- :ref:`Backend Routes <t3coreapi:backend-routing>`
- :ref:`Backend Modules <t3coreapi:backend-modules>`
- :ref:`HTTP Middlewares (PSR-15) <t3coreapi:request-handling>`
- :ref:`Sites: TCA configuration <t3coreapi:sitehandling>`
- :ref:`Sites: YAML configuration <t3coreapi:sitehandling>`
- :ref:`Event listeners (PSR-14) <t3coreapi:EventDispatcher>`
- :ref:`MFA providers <t3coreapi:multi-factor-authentication>`
- :ref:`Soft Reference Parsers <t3coreapi:soft-references>`
- :ref:`Form: YAML Configuration <ext_form:concepts-configuration>` (with installed :doc:`Form system extension <ext_form:Index>`)
- `Backend Toolbar Items`
- :ref:`Symfony Expression Language Providers <t3coreapi:sel-ts-registering-new-provider-within-extension>`
- :ref:`Reactions <ext_reactions:reactions-overview>` (with installed :doc:`Reactions system extension <ext_reactions:Index>`)
- :ref:`Content Security Policy Mutations <t3coreapi:content-security-policy>`
- :ref:`Doctrine DBAL Driver Middlewares <t3coreapi:database-middleware>`

..  _module-configuration-extending:

Extending the Configuration module
==================================

To make this module more powerful a dedicated API is available which
allows extension authors to extend the module so they can expose their own
configurations.

By the nature of the API it is even possible to not just add new
configuration but to also disable the display of existing configuration,
if not needed in the specific installation.

..  _module-configuration-extending-provider:

Configuration module provider: Basic implementation
---------------------------------------------------

To extend the configuration module, a custom configuration provider needs to
be registered. Each "provider" is responsible for one configuration. The provider
is registered as a so-called "configuration module provider" by tagging it in the
:file:`Services.yaml` file. The provider class must implement
the :t3src:`lowlevel/Classes/ConfigurationModuleProvider/ProviderInterface.php`.

The registration of such a provider looks like the following:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    myextension.configuration.module.provider.myconfiguration:
        class: 'Vendor\Extension\ConfigurationModuleProvider\MyProvider'
        tags:
            - name: 'lowlevel.configuration.module.provider'
              identifier: 'myProvider'
              before: 'beUserTsConfig'
              after: 'pagesTypes'

A new service with a freely selectable name is defined by specifying the
provider class to be used. Further, the new service must be tagged with the
`lowlevel.configuration.module.provider` tag. Arbitrary attributes
can be added to this tag. However, some are reserved and required for internal
processing. For example, the `identifier` attribute is mandatory and must be
unique. Using the `before` and `after` attributes, it is possible to specify
the exact position on which the configuration will be displayed in the module
menu.

The provider class has to implement the methods as required by the interface.
A full implementation would look like this:

..  literalinclude:: _codesnippets/_MyProvider.php
    :caption: EXT:my_extension/Classes/ConfigurationModule/MyProvider.php

The :php:`__invoke()` method is called from the provider registry and provides
all attributes, defined in the :file:`Services.yaml`. This can be used to set
and initialize class properties like the :php`$identifier` which can then be returned
by the required method :php:`getIdentifier()`. The :php:`getLabel()` method is
called by the configuration module when creating the module menu. And finally,
the :php:`getConfiguration()` method has to return the configuration as an
:php:`array` to be displayed in the module.

There is also the abstract class
:t3src:`lowlevel/Classes/ConfigurationModuleProvider/AbstractProvider.php` in place
which already implements the required methods; except :php:`getConfiguration()`.
Please note, when extending this class, the attribute `label` is expected in the
`__invoke()` method and must therefore be defined in the :file:`Services.yaml`.
Either a static text or a :ref:`localized label <t3coreapi:localization>` can be used.

Since the registration uses the Symfony service container and provides all
attributes using :php:`__invoke()`, it is even possible to use
:ref:`dependency injection <t3coreapi:DependencyInjection>` with constructor arguments in
the provider classes.

..  _module-configuration-extending-globals:

Displaying custom values from `$GLOBALS`
----------------------------------------

If you want to display a custom configuration from the :php:`$GLOBALS` array,
you can also use the already existing
:php:`TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\GlobalVariableProvider`.
Define the key to be exposed using the `globalVariableKey` attribute.

This could look like this:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    myextension.configuration.module.provider.myconfiguration:
        class: 'TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\GlobalVariableProvider'
        tags:
            - name: 'lowlevel.configuration.module.provider'
              identifier: 'myConfiguration'
              label: 'My global var'
              globalVariableKey: 'MY_GLOBAL_VAR'

..  _module-configuration-disable:

Disabling an entry
------------------

To disable an already registered configuration add the :yaml:`disabled` attribute
set to :yaml:`true`. For example, if you intend to disable the `T3_SERVICES` key
you can use:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    lowlevel.configuration.module.provider.services:
        class: TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\GlobalVariableProvider
        tags:
            - name: 'lowlevel.configuration.module.provider'
              disabled: true

..  _config-module-blind-options:

Blinding configuration options
==============================

Sensitive data (like passwords or access tokens) should not be displayed in the
configuration module. Therefore, the PSR-14 event
:ref:`ModifyBlindedConfigurationOptionsEvent` is available to blind such
configuration options.
