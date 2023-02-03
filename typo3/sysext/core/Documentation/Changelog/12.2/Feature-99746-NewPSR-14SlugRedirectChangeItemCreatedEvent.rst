.. include:: /Includes.rst.txt

.. _feature-99746-1675059434:

===============================================================
Feature: #99746 - New PSR-14 SlugRedirectChangeItemCreatedEvent
===============================================================

See :issue:`99746`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent`
has been added to TYPO3 Core. This event is fired in the
:php:`\TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItemFactory` and
allows extension authors to manage the redirect sources for which redirects
should be created.

The event features the following methods:

-   :php:`getSlugRedirectChangeItem()`: Returns the current
    :php:`\TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem`
-   :php:`setSlugRedirectChangeItem()`: Can be used to set a new or changed
    :php:`SlugRedirectChangeItem`

TYPO3 already implements the :php:`\TYPO3\CMS\Redirects\EventListener\AddPlainSlugReplacementSource`
listener. It is used to add the plain slug value based source type, which provides the same
behaviour like before. Implementing this as a Core listener gives extension authors the ability to
remove the source added by :php:`AddPlainSlugReplacementSource`, when their listeners are
registered and executed afterwards. See the example below.

It is required for custom source class implementations to implement the
:php:`\TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceInterface`. Using the
interface allows to detect custom source class implementations automatically.
Additionally, this allows to transport custom information and data.

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\Redirects\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-extension/redirects/add-redirect-source'
          after: 'redirects-add-plain-slug-replacement-source'

The corresponding event listener class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Redirects/MyEventListener.php

    namespace MyVendor\MyExtension\Redirects;

    use MyVendor\MyExtension\Redirects\CustomSource;
    use TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent;
    use TYPO3\CMS\Redirects\RedirectUpdate\PlainSlugReplacementRedirectSource;
    use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;

    final class MyEventListener {
        public function __invoke(SlugRedirectChangeItemCreatedEvent $event): void
        {
            // Retrieve change item and sources
            $changeItem = $event->getSlugRedirectChangeItem();
            $sources = $changeItem->getSourcesCollection()->all();

            // remove plain slug replacement redirect source from sources
            $sources = array_filter(
                $sources,
                fn ($source) => !($source instanceof PlainSlugReplacementRedirectSource)
            );

            // add custom source implementation
            $sources[] = new CustomSource();

            // replace sources collection
            $changeItem = $changeItem->withSourcesCollection(
                new RedirectSourceCollection(...array_values($sources))
            );

            // Update changeItem in the event
            $event->setSlugRedirectChangeItem($changeItem);
        }
    }

Custom source implementation (example):

..  code-block:: php
    :caption: EXT:my_extension/Classes/Redirects/CustomSource.php

    namespace MyVendor\MyExtension\Redirects;

    use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceInterface;

    final class CustomSource implements RedirectSourceInterface
    {
        public function getHost(): string
        {
            return '*';
        }

        public function getPath(): string
        {
            return '/some-path';
        }

        public function getTargetLinkParameters(): array
        {
            return [];
        }
    }

Impact
======

With the new :php:`SlugRedirectChangeItemCreatedEvent`, it is possible to manage
the redirect sources for which redirects should be created. It furthermore allows
to influence existing Core functionality.

.. index:: PHP-API, ext:redirects
