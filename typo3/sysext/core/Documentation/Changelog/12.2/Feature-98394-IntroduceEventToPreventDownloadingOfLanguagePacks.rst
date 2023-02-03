.. include:: /Includes.rst.txt

.. _feature-98394-1674070213:

==========================================================================
Feature: #98394 - Introduce event to prevent downloading of language packs
==========================================================================

See :issue:`98394`

Description
===========

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:
      MyVendor\MyExtension\EventListener\ModifyLanguagePacks:
        tags:
          - name: event.listener
            identifier: 'modifyLanguagePacks'
            event: TYPO3\CMS\Install\Service\Event\ModifyLanguagePacksEvent
            method: 'modifyLanguagePacks'


..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ModifyLanguagePacks.php

    <?php
    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Install\Service\Event\ModifyLanguagePacksEvent;

    final class ModifyLanguagePacks
    {
        public function modifyLanguagePacks(ModifyLanguagePacksEvent $event): void
        {
            $extensions = $event->getExtensions();
            foreach ($extensions as $key => $extension){
                if($extension['type'] === 'typo3-cms-framework'){
                    $event->removeExtension($key);
                }
            }
            $event->removeIsoFromExtension('de', 'styleguide');
        }
    }

Impact
======

With the newly introduced event, it is possible to ignore extensions or
individual language packs for extensions when downloading the language packs.
However, only language packs for extensions and languages
available in the system can be downloaded. The options of the `language:update`
command can be used to further restrict the download (ignore additional
extensions or download only specific languages), but not to ignore decisions
made by the event.

.. index:: ext:install
