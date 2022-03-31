.. include:: /Includes.rst.txt

==========================================================================
Feature: #86614 - Add PSR-14 event to control hreflang tags to be rendered
==========================================================================

See :issue:`86614`

Description
===========

It is now possible to alter the hreflang tags just before they
get rendered. You can do this by registering an event listener for
the event :php:`TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent`.

Also the class :php:`TYPO3\CMS\Seo\HrefLang\HrefLangGenerator` has been
refactored to be a listener (identifier :php:`'typo3-seo/hreflangGenerator'`)
to the newly introduced event. This way the system extension seo still
provides hreflang tags but it is now possible to simply register
after or instead of the implementation.

Example
=======

An example implementation could look like this:

:file:`EXT:my_extension/Configuration/Services.yaml`

.. code-block:: yaml

   services:
     Vendor\MyExtension\HrefLang\EventListener\OwnHrefLang:
       tags:
         - name: event.listener
           identifier: 'my-ext/ownHrefLang'
           after: 'typo3-seo/hreflangGenerator'
           event: TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent

With :yaml:`after` and :yaml:`before`, you can make sure your own listener is
executed after or before the given identifiers.

:file:`EXT:my_extension/Classes/HrefLang/EventListener/OwnHrefLang.php`

.. code-block:: php

   namespace Vendor\MyExtension\HrefLang\EventListener;

   use TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent;

   class OwnHrefLang
   {
      public function __invoke(ModifyHrefLangTagsEvent $event): void
      {
         $hrefLangs = $event->getHrefLangs();
         $request = $event->getRequest();

         // Do anything you want with $hrefLangs
         $hrefLangs = [
            'en-US' => 'https://example.com',
            'nl-NL' => 'https://example.com/nl'
         ];

         // Override all hrefLang tags
         $event->setHrefLangs($hrefLangs);

         // Or add a single hrefLang tag
         $event->addHrefLang('de-DE', 'https://example.com/de');
       }
   }

.. index:: ext:seo, PHP-API
