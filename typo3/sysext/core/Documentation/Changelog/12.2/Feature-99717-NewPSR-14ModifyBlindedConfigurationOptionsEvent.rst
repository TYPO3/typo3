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

By now, the new PSR-14 event is fired in the :php:`GlobalVariableProvider`,
while building the configuration array, which should be displayed in the
:guilabel:`Configuration` module and therefore allows to blind (hide) any of
those configuration options. Usually, such options are passwords or any other
sensitive information. In the future, the PSR-14 Event might be fired in other
configuration providers, too.

Example
=======

Registration of the :php:`ModifyBlindedConfigurationOptionsEvent` in your
extensions' :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Backend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/blind-configuration-options'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;

    class MyEventListener {

        public function __invoke(ModifyBlindedConfigurationOptionsEvent $event): void
        {
            $blindedConfigurationOptions = $event->getBlindedConfigurationOptions();

            $blindedConfigurationOptions['TYPO3_CONF_VARS']['EXTENSIONS']['my_extension']['password'] = '******';

            $event->setBlindedConfigurationOptions($blindedConfigurationOptions);
        }
    }

Impact
======

With the new :php:`ModifyBlindedConfigurationOptionsEvent`, it's now
possible to modify any global configuration option, displayed in the
:guilabel:`Configuration` module.

.. index:: Backend, LocalConfiguration, PHP-API, ext:lowlevel
