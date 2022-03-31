.. include:: /Includes.rst.txt

=================================================
Feature: #92929 - Extendable configuration module
=================================================

See :issue:`92929`

Description
===========

Since a long time, the configuration module in EXT:lowlevel was the first
stop for integrators when it came to validation of the global configuration.
The module displays all relevant global variables such as
:php:`TYPO3_CONF_VARS`, :php:`TCA` and many more, in a tree format which is
easy to browse through. Over time this module got extended to also display
the configuration of newly introduced features like the middleware stack or
the event listeners.

To make this module even more powerful, a dedicated API was introduced which
allows extension authors to extend the module so they can expose their own
configurations.

By the nature of the new API it is even possible to not just add new
configuration but to also disable existing, if not needed in the specific
installation.

So, how does it work?
**********************

Each "provider", responsible for one configuration, is registered as a so-called
"configuration module provider". This is done in the corresponding
:file:`Services.yaml` file of each extension. Each provider is tagged and will
then automatically be registered. Therefore, the provider class must implement
the :php:`ProviderInterface` which requires some methods to be present in the
provider class so they can be called from within the module.

The registration of such a provider looks like the following:

.. code-block:: yaml

   myextension.configuration.module.provider.myconfiguration:
     class: 'Vendor\Extension\ConfigurationModuleProvider\MyProvider'
     tags:
       - name: 'lowlevel.configuration.module.provider'
         identifier: 'myProvider'
         before: 'beUserTsConfig'
         after: 'pagesTypes'

A new service with a freely selectable name is defined by specifying the
provider class to be used. Further, the new service must be tagged with the
:yaml:`lowlevel.configuration.module.provider` tag. Arbitrary attributes
can be added to this tag. However, some are reserved and required for internal
processing. For example, the :yaml:`identifier` attribute is mandatory and must be
unique. Using the :yaml:`before` and :yaml:`after` attributes, it is possible to specify
the exact position on which the configuration will be displayed in the module
menu.

The provider class has to implement some methods, required by the interface.
A full implementation would look like this:

.. code-block:: php

   <?php

   use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderInterface;

   class MyProvider implements ProviderInterface
   {
      protected string $identifier;

      public function __invoke(array $attributes): self
      {
         $this->identifier = $attributes['identifier'];
         return $this;
      }

      public function getIdentifier(): string
      {
         return $this->identifier;
      }

      public function getLabel(): string
      {
         return 'My custom configuration';
      }

      public function getConfiguration(): array
      {
         return $myCustomConfiguration;
      }
   }

The :php:`__invoke()` method is called from the provider registry and provides
all attributes, defined in the :file:`Services.yaml`. This can be used to set
and initialize class properties like the `$identifier` which can then be returned
by the required method :php:`getIdentifier()`. The :php:`getLabel()` method is
called by the configuration module when creating the module menu. And finally,
the :php:`getConfiguration()` method has to return the configuration as an
:php:`array` to be displayed in the module.

There is also the abstract class
:php:`TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\AbstractProvider` in place
which already implements the required methods except :php:`getConfiguration`.
Please note, when extending this class, the attribute `label` is expected in the
`__invoke()` method and must therefore be defined in the :file:`Services.yaml`.
Either a static text or a locallang label can be used.

Since the registration uses the Symfony service container and provides all
attributes using :php:`__invoke()`, it is even possible to use DI with
constructor arguments in the provider classes.

If you just want to display a custom configuration from the `$GLOBALS` array,
you can also use the already existing
:php:`TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\GlobalVariableProvider`.
Simply define the key to be exposed using the `globalVariableKey` attribute.

This could look like this:

.. code-block:: yaml

   myextension.configuration.module.provider.myconfiguration:
     class: 'TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\GlobalVariableProvider'
     tags:
       - name: 'lowlevel.configuration.module.provider'
         identifier: 'myConfiguration'
         label: 'My global var'
         globalVariableKey: 'MY_GLOBAL_VAR'

To disable an already registered configuration simply add the :yaml:`disabled: true`
attribute. For example, if you intend to disable the :php:`TCA_DESCR` key you can use:

.. code-block:: yaml

   lowlevel.configuration.module.provider.tcadescr:
     class: TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\GlobalVariableProvider
     tags:
       - name: 'lowlevel.configuration.module.provider'
         disabled: true

Impact
======

It is now possible to extend the configuration module for custom configurations
and to manage the available options for the module by disabling any provider
shipped by core or another third-party extension.

.. index:: Backend, PHP-API, ext:lowlevel
