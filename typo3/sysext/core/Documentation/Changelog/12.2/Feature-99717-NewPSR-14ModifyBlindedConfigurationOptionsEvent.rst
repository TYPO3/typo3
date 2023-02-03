.. include:: /Includes.rst.txt

.. _feature-99717-1674654720:

===================================================================
Feature: #99717 - New PSR-14 ModifyBlindedConfigurationOptionsEvent
===================================================================

See :issue:`99717`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent`
has been introduced which serves as a direct replacement for the
now deprecated hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Lowlevel\Controller\ConfigurationController']['modifyBlindedConfigurationOptions']`.

The new PSR-14 event is fired in the
:php:`\TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\GlobalVariableProvider` and the
:php:`\TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\SitesYamlConfigurationProvider`,
while building the corresponding configuration array, which should be displayed
in the :guilabel:`Configuration` module. The event therefore allows to
blind (hide) any of those configuration options. Usually, such options are
passwords or any other sensitive information.

Using the :php:`ModifyBlindedConfigurationOptionsEvent::getProviderIdentifier()`
method, listeners are able to determine the context, the event got dispatched
in. This is useful to prevent duplicate code execution, since the event is
dispatched for multiple providers. The method returns the identifier of
the configuration provider as registered in the
:ref:`service configuration <feature-92929>`.

Example
=======

Registration of the :php:`ModifyBlindedConfigurationOptionsEvent` in your
extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Backend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/blind-configuration-options'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Backend/MyEventListener.php

    namespace MyVendor\MyExtension\Backend;

    use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\GlobalVariableProvider;
    use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\SitesYamlConfigurationProvider;
    use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;

    final class MyEventListener {
        public function __invoke(ModifyBlindedConfigurationOptionsEvent $event): void
        {
            $blindedConfigurationOptions = $event->getBlindedConfigurationOptions();

            if ($event->getProviderIdentifier() === 'sitesYamlConfiguration') {
                $blindedConfigurationOptions['my-site']['settings']['apiKey'] = '***';
            } elseif ($event->getProviderIdentifier() === 'confVars') {
                $blindedConfigurationOptions['TYPO3_CONF_VARS']['EXTENSIONS']['my_extension']['password'] = '******';
            }

            $event->setBlindedConfigurationOptions($blindedConfigurationOptions);
        }
    }

Impact
======

With the new :php:`ModifyBlindedConfigurationOptionsEvent`, it is
now possible to modify global configuration options as well as site
configuration options, displayed in the :guilabel:`Configuration` module.

The event might be triggered by more configuration providers in the future.

.. index:: Backend, LocalConfiguration, PHP-API, ext:lowlevel
