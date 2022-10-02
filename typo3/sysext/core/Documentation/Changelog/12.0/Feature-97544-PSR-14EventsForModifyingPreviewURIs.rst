.. include:: /Includes.rst.txt

.. _feature-97544:

==========================================================
Feature: #97544 - PSR-14 events for modifying preview URIs
==========================================================

See :issue:`97544`

Description
===========

Two new PSR-14 events :php:`\TYPO3\CMS\Backend\Routing\Event\BeforePagePreviewUriGeneratedEvent`
and :php:`\TYPO3\CMS\Backend\Routing\Event\AfterPagePreviewUriGeneratedEvent`
have been introduced. Those serve as a direct replacement for the now deprecated
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']`
:doc:`hook <../12.0/Deprecation-97544-PreviewURIGenerationRelatedFunctionalityInBackendUtility>`.

The :php:`BeforePagePreviewUriGeneratedEvent` is executed in the
:php:`PreviewUriBuilder->buildUri()`, before the preview URI is actually built.
It allows to either adjust the parameters, such as the page ID or the language ID,
or to set a custom preview URI, which will then stop the event propagation and
also prevents :php:`PreviewUriBuilder` from building the URI based on the
parameters.

Methods of :php:`BeforePagePreviewUriGeneratedEvent`:

- :php:`setPreviewUri(UriInterface $uri)`
- :php:`getPageId()`
- :php:`setPageId(int $pageId)`
- :php:`getLanguageId()`
- :php:`setLanguageId(int $languageId)`
- :php:`getRootline()`
- :php:`setRootline(array $rootline)`
- :php:`getSection()`
- :php:`setSection(string $section)`
- :php:`getAdditionalQueryParameters()`
- :php:`setAdditionalQueryParameters(array $additionalQueryParameters)`
- :php:`getContext()`
- :php:`getOptions()`

.. note::

    The overwritten parameters are used for building the URI and are also
    passed to the :php:`AfterPagePreviewUriGeneratedEvent`. They however
    do not overwrite the related class properties in :php:`PreviewUriBuilder`.

The :php:`AfterPagePreviewUriGeneratedEvent` is executed in the
:php:`PreviewUriBuilder->buildUri()`, after the preview URI has been built -
or set by an event listener to :php:`BeforePagePreviewUriGeneratedEvent`. It
allows to overwrite the built preview URI. This event however does not feature
the possibility to modify the parameters, since this won't have any effect as
the preview URI is directly returned after event dispatching and no
further action is done by the :php:`PreviewUriBuilder`.

Methods of :php:`AfterPagePreviewUriGeneratedEvent`:

- :php:`setPreviewUri(UriInterface $uri)`
- :php:`getPreviewUri()`
- :php:`getPageId()`
- :php:`getLanguageId()`
- :php:`getRootline()`
- :php:`getSection()`
- :php:`getAdditionalQueryParameters()`
- :php:`getContext()`
- :php:`getOptions()`

Example
=======

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Backend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/backend/modify-parameters'
          method: 'modifyParameters'
        - name: event.listener
          identifier: 'my-package/backend/modify-preview-uri'
          method: 'modifyPreviewUri'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Backend\Routing\Event\AfterPagePreviewUriGeneratedEvent;
    use TYPO3\CMS\Backend\Routing\Event\BeforePagePreviewUriGeneratedEvent;

    final class MyEventListener
    {
        public function modifyParameters(BeforePagePreviewUriGeneratedEvent $event): void
        {
            // Add custom query parameter before URI generation
            $event->setAdditionalQueryParameters(
                array_replace_recursive(
                    $event->getAdditionalQueryParameters(),
                    ['myParam' => 'paramValue']
                )
            );
        }

        public function modifyPreviewUri(AfterPagePreviewUriGeneratedEvent $event): void
        {
            // Add custom fragment to built preview URI
            $uri = $event->getPreviewUri();
            $uri = $uri->withFragment('#customFragment');
            $event->setPreviewUri($uri);
        }
    }

Impact
======

It's now possible to modify the parameters used to build a preview URI and
also to directly set a custom preview URI, using the new PSR-14 event
:php:`BeforePagePreviewUriGeneratedEvent`. It's also now possible to
modify or completely replace a built preview URI using the new PSR-14 event
:php:`AfterPagePreviewUriGeneratedEvent`.

.. index:: Backend, Frontend, PHP-API, ext:backend
