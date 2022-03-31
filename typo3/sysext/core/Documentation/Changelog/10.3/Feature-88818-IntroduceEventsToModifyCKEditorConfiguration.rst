.. include:: /Includes.rst.txt

===================================================================
Feature: #88818 - Introduce events to modify CKEditor configuration
===================================================================

See :issue:`88818`

Description
===========

The following new PSR-14-based Events are introduced which allow
to modify CKEditor configuration.

- :php:`TYPO3\CMS\RteCKEditor\Form\Element\Event\AfterGetExternalPluginsEvent`
- :php:`TYPO3\CMS\RteCKEditor\Form\Element\Event\BeforeGetExternalPluginsEvent`
- :php:`TYPO3\CMS\RteCKEditor\Form\Element\Event\AfterPrepareConfigurationForEditorEvent`
- :php:`TYPO3\CMS\RteCKEditor\Form\Element\Event\BeforePrepareConfigurationForEditorEvent`

Example
=======

An example implementation how you could extend the existing
configuration to register a new plugin:

:file:`EXT:my_extension/Configuration/Services.yaml`

.. code-block:: yaml

   services:
     Vendor\MyExtension\EventListener\RteConfigEnhancer:
       tags:
         - name: event.listener
           identifier: 'ext-myextension/rteConfigEnhancer'
           method: 'beforeGetExternalPlugins'
           event: TYPO3\CMS\RteCKEditor\Form\Element\Event\BeforeGetExternalPluginsEvent
         - name: event.listener
           identifier: 'ext-myextension/rteConfigEnhancer'
           method: 'beforePrepareConfiguration'
           event: TYPO3\CMS\RteCKEditor\Form\Element\Event\BeforePrepareConfigurationForEditorEvent

:file:`EXT:my_extension/Classes/EventListener/RteConfigEnhancer.php`

.. code-block:: php

   namespace Vendor\MyExtension\EventListener;

   use TYPO3\CMS\RteCKEditor\Form\Element\Event\BeforeGetExternalPluginsEvent;
   use TYPO3\CMS\RteCKEditor\Form\Element\Event\BeforePrepareConfigurationForEditorEvent;

   class RteConfigEnhancer
   {
      public function beforeGetExternalPlugins(BeforeGetExternalPluginsEvent $event): void
      {
         $data = $event->getData();
         // @todo make useful decisions on fetched data
         $configuration = $event->getConfiguration();
         $configuration['example_plugin'] = [
            'resource' => 'EXT:my_extension/Resources/Public/CKEditor/Plugins/ExamplePlugin/plugin.js'
         ];
         $event->setConfiguration($configuration);
      }

      public function beforePrepareConfiguration(BeforePrepareConfigurationForEditorEvent $event): void
      {
         $data = $event->getData();
         // @todo make useful decisions on fetched data
         $configuration = $event->getConfiguration();
         $configuration['extraPlugins'][] = 'example_plugin';
         $event->setConfiguration($configuration);
      }
   }

.. index:: Backend, PHP-API, RTE, ext:rte_ckeditor
