.. include:: /Includes.rst.txt

==================================================================
Feature: #91132 - Introduce User Settings JavaScript Modules Event
==================================================================

See :issue:`91132`

Description
===========

JavaScript events in custom User Settings Configuration options shall
not be placed as inline JavaScript anymore, but utilize a dedicated
JavaScript module to handle custom events
(see :doc:`Important-91132-AvoidJavaScriptInUserSettingsConfigurationOptions`)

This new PSR-14 event is introduced:

* :php:`\TYPO3\CMS\SetupEvent\AddJavaScriptModulesEvent`

These public methods are exposed:

* :php:`public function addModule(string $moduleName): void`
* :php:`public function getModules(): array`

:php:`$moduleName` refers to the JavaScript module to be loaded with RequireJS
(e.g. `TYPO3/CMS/MyExtension/CustomUserSettingsModule`).


Example
=======

A listener using mentioned PSR-14 event could look like the following.

.. rst-class:: bignums

   1. Register listener

      :file:`typo3conf/my-extension/Configuration/Services.yaml`

      .. code-block:: yaml

         services:
            MyVendor\MyExtension\EventListener\CustomUserSettingsListener:
             tags:
               - name: event.listener
                 identifier: 'myExtension/CustomUserSettingsListener'
                 event: TYPO3\CMS\SetupEvent\AddJavaScriptModulesEvent


   2. Implement Listener to load JavaScript module `TYPO3/CMS/MyExtension/CustomUserSettingsModule`

      .. code-block:: php

         namespace MyVendor\MyExtension\EventListener;

         use TYPO3\CMS\SetupEvent\AddJavaScriptModulesEvent;

         class CustomUserSettingsListener
         {
             // name of JavaScript module to be loaded
             private const MODULE_NAME = 'TYPO3/CMS/MyExtension/CustomUserSettingsModule';

             public function __invoke(AddJavaScriptModulesEvent $event): void
             {
                 $javaScriptModuleName = 'TYPO3/CMS/MyExtension/CustomUserSettings';
                 if (in_array(self::MODULE_NAME, $event->getModules(), true)) {
                     return;
                 }
                 $event->addModule(self::MODULE_NAME);
             }
         }


Related
=======

- :doc:`Important-91132-AvoidJavaScriptInUserSettingsConfigurationOptions`

.. index:: PHP-API, ext:core
