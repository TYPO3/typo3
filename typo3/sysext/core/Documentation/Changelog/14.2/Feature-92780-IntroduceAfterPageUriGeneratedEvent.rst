..  include:: /Includes.rst.txt

..  _feature-92780-1761709200:

============================================================
Feature: #92780 - Introduce event after page URI generation
============================================================

See :issue:`92780`

Description
===========

A new PSR-14 event
:php:`\TYPO3\CMS\Core\Routing\Event\AfterPageUriGeneratedEvent` is
dispatched in :php:`\TYPO3\CMS\Core\Routing\PageRouter::generateUri()`.

The event provides access to the generated URI and the arguments passed to
:php:`generateUri()`. Listeners can inspect and replace the generated URI.
The :php:`parameters` payload reflects the sanitized query arguments after
handling special parameters such as :php:`id` and :php:`_language`.

The event has the following methods:

*   :php:`getUri()` and :php:`setUri()`
*   :php:`getRoute()`
*   :php:`getParameters()`
*   :php:`getFragment()`
*   :php:`getType()`
*   :php:`getLanguage()`
*   :php:`getSite()`

..  attention::

    :php:`PageRouter::generateUri()` is called from many different contexts
    across TYPO3 core, not only during frontend page rendering. This event
    therefore fires for URIs generated in the backend, including page preview,
    FormEngine, new-record redirects, workspace preview links, XML sitemaps,
    redirect source detection, webhook payloads, and error handlers, among
    others.

    Listeners that modify the URI must therefore be context-aware. Use
    :php:`getType()` (see
    :php:`\TYPO3\CMS\Core\Routing\RouterInterface`) to distinguish between
    an absolute URL (:php:`RouterInterface::ABSOLUTE_URL`) and an absolute
    path (:php:`RouterInterface::ABSOLUTE_PATH`), and use
    :php:`getSite()`, :php:`getLanguage()`, or :php:`getRoute()` to limit
    modifications to the intended context. Unconditionally replacing URIs can
    break backend previews, sitemaps, or other subsystems in non-obvious ways.

When replacing the URI, listeners must ensure that the returned URI is valid in
their setup and remains routable.

Example listener registration:

..  code-block:: php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Routing\Event\AfterPageUriGeneratedEvent;
    use TYPO3\CMS\Core\Routing\RouterInterface;

    #[AsEventListener('my-extension/after-page-uri-generated')]
    final readonly class MyListener
    {
        public function __invoke(AfterPageUriGeneratedEvent $event): void
        {
            // Only act on absolute URLs
            if ($event->getType() !== RouterInterface::ABSOLUTE_URL) {
                return;
            }

            // Inspect or replace $event->getUri()
        }
    }

Impact
======

Extension authors can now react to generated page URIs for use cases such as
logging, monitoring, debugging, and URL adjustment. Because the event is
dispatched in all contexts that use :php:`PageRouter::generateUri()`, including
the backend and various subsystems, listeners should scope their modifications
carefully.

..  index:: PHP-API, ext:core
