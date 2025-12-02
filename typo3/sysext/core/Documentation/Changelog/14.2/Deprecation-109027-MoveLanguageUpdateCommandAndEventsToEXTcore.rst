..  include:: /Includes.rst.txt

..  _deprecation-109027-1771514240:

===============================================================================
Deprecation: #109027 - Move `language:update` command and events to `EXT:core`
===============================================================================

See :issue:`109027`

Description
===========

The `language:update` CLI command and the related
:php:`\TYPO3\CMS\Install\Service\LanguagePackService` have been moved from
`EXT:install` to `EXT:core`, allowing installations to update language packs
without requiring `EXT:install` to be installed.

Since TYPO3 v13, it is possible to run TYPO3 without `EXT:install` in composer
based installations. However, the `language:update` command still required
`EXT:install`, which made this advantage impractical for deployments that need
to update language packs.

The following classes have been moved and their old class names deprecated:

*  :php:`\TYPO3\CMS\Install\Command\LanguagePackCommand` is now
   :php:`\TYPO3\CMS\Core\Command\UpdateLanguagePackCommand`
*  :php:`\TYPO3\CMS\Install\Service\Event\ModifyLanguagePackRemoteBaseUrlEvent`
   is now :php:`\TYPO3\CMS\Core\Localization\Event\ModifyLanguagePackRemoteBaseUrlEvent`
*  :php:`\TYPO3\CMS\Install\Service\Event\ModifyLanguagePacksEvent` is now
   :php:`\TYPO3\CMS\Core\Localization\Event\ModifyLanguagePacksEvent`

The old class names are registered as aliases via :php:`ClassAliasMap` and
continue to work in TYPO3 v14. Event listeners registered for the deprecated
event class names are still called when the new event is dispatched, with a
deprecation notice triggered at runtime.

Impact
======

Using the old class names will trigger a deprecation notice. The extension
scanner will report usages of the deprecated class names.

The old class names will be removed in TYPO3 v15.

Affected installations
======================

Extensions that use one or more of the deprecated class names listed above.

Migration
=========

Replace the old class names with the new ones in :php:`use` statements:

..  code-block:: diff
    :caption: EXT:my_extension/Classes/EventListener/MyEventListener.php

     <?php

     declare(strict_types=1);

     namespace MyVendor\MyExtension\EventListener;

     use TYPO3\CMS\Core\Attribute\AsEventListener;
    -use TYPO3\CMS\Install\Service\Event\ModifyLanguagePacksEvent;
    +use TYPO3\CMS\Core\Localization\Event\ModifyLanguagePacksEvent;

     final class MyEventListener
     {
         #[AsEventListener(
             identifier: 'my-extension/modify-language-packs',
         )]
         public function __invoke(
             ModifyLanguagePacksEvent $event,
         ): void {
             // ...
         }
     }

..  code-block:: diff
    :caption: EXT:my_extension/Classes/EventListener/MyOtherEventListener.php

     <?php

     declare(strict_types=1);

     namespace MyVendor\MyExtension\EventListener;

     use TYPO3\CMS\Core\Attribute\AsEventListener;
    -use TYPO3\CMS\Install\Service\Event\ModifyLanguagePackRemoteBaseUrlEvent;
    +use TYPO3\CMS\Core\Localization\Event\ModifyLanguagePackRemoteBaseUrlEvent;

     final class MyOtherEventListener
     {
         #[AsEventListener(
             identifier: 'my-extension/modify-language-pack-remote-base-url',
         )]
         public function __invoke(
             ModifyLanguagePackRemoteBaseUrlEvent $event,
         ): void {
             // ...
         }
     }

..  note::

    Extensions supporting both TYPO3 v13 and v14 do not need to change
    anything. The old class names continue to work in both versions.
    Simply update the :php:`use` statements when dropping TYPO3 v13 support.

..  index:: CLI, PHP-API, FullyScanned, ext:install
