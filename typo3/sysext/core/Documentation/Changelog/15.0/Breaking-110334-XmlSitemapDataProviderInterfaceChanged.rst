..  include:: /Includes.rst.txt

..  _breaking-110334-1785418299:

===============================================================
Breaking: #110334 - XML sitemap data provider interface changed
===============================================================

See :issue:`110334`

Description
===========

:php:`\TYPO3\CMS\Seo\XmlSitemap\XmlSitemapDataProviderInterface` declared a
constructor, which forced every data provider to be instantiated with its
runtime information as constructor arguments. Data providers could therefore
never be resolved by the dependency injection container and had to fetch all
their dependencies statically. In addition, the items of a sitemap had to be
collected in the constructor, since the interface methods took no arguments.

The interface has been changed accordingly:

-   The constructor declaration and the methods :php:`getKey()`,
    :php:`getItems()`, :php:`getLastModified()` and :php:`getNumberOfPages()`
    have been removed in favor of the single method :php:`getSitemap()`.

-   :php:`getSitemap()` receives all runtime information as
    :php:`\TYPO3\CMS\Seo\XmlSitemap\XmlSitemapRequest`: The name of the
    requested sitemap, its configuration, the number of the requested page, the
    current request and a content object renderer to generate URLs with.

-   :php:`getSitemap()` returns a :php:`\TYPO3\CMS\Seo\XmlSitemap\XmlSitemap`
    carrying the items of the requested page, the last modification date and
    the number of pages of the sitemap.

-   Data providers are stateless services now. The interface is tagged with
    :yaml:`seo.xmlsitemap.provider`, so implementations are registered in the
    dependency injection container automatically and are free to use
    constructor injection.

Registering a sitemap in TypoScript is unchanged: Data providers are still
referenced by their class name.

Impact
======

Classes implementing :php:`XmlSitemapDataProviderInterface` without the method
:php:`getSitemap()` will cause a fatal PHP error.

Data providers that are not available as a service in the dependency injection
container are not resolved anymore: Rendering such a sitemap throws a
:php:`\TYPO3\CMS\Seo\XmlSitemap\Exception\InvalidConfigurationException`, and
the sitemap is skipped in the sitemap index.

Data providers extending
:php:`\TYPO3\CMS\Seo\XmlSitemap\AbstractXmlSitemapDataProvider` keep working
unchanged, see :ref:`deprecation-110334-1785418299`.

Affected installations
======================

All installations with custom extensions implementing
:php:`XmlSitemapDataProviderInterface` directly. This is a rarely used API,
most data providers extend :php:`AbstractXmlSitemapDataProvider` instead.

Migration
=========

Move the collecting of items from the constructor to :php:`getSitemap()` and
take the configuration, the requested page and the content object renderer
from the :php:`XmlSitemapRequest`. The constructor is free for dependency
injection afterwards:

..  code-block:: php
    :caption: EXT:my_extension/Classes/XmlSitemap/MyXmlSitemapDataProvider.php

    use TYPO3\CMS\Seo\XmlSitemap\XmlSitemap;
    use TYPO3\CMS\Seo\XmlSitemap\XmlSitemapDataProviderInterface;
    use TYPO3\CMS\Seo\XmlSitemap\XmlSitemapRequest;

    final readonly class MyXmlSitemapDataProvider implements XmlSitemapDataProviderInterface
    {
        public function __construct(
            private MyItemRepository $itemRepository,
        ) {}

        public function getSitemap(XmlSitemapRequest $sitemapRequest): XmlSitemap
        {
            $items = [];
            foreach ($this->itemRepository->findAll($sitemapRequest->configuration) as $item) {
                $items[] = [
                    'uid' => $item['uid'],
                    'lastMod' => $item['tstamp'],
                ];
            }
            return XmlSitemap::forPage(
                $items,
                $sitemapRequest->page,
                fn(array $item): array => $this->defineUrl($item, $sitemapRequest),
            );
        }

        private function defineUrl(array $item, XmlSitemapRequest $sitemapRequest): array
        {
            $item['loc'] = $sitemapRequest->contentObjectRenderer->createUrl([
                'parameter' => $item['uid'],
                'forceAbsoluteUrl' => 1,
            ]);
            return $item;
        }
    }

:php:`XmlSitemap::forPage()` takes all items of a sitemap, extracts the items of
the requested page and calculates the last modification date and the number of
pages needed by the sitemap index. The optional item mapper is applied to the
items of the requested page only, and only when the items are rendered at all -
the sitemap index does not generate any URL this way. Data providers taking care
of paging themselves, for example by limiting their database query to the items
of the requested page, use :php:`XmlSitemap::create()` instead:

..  code-block:: php

    return XmlSitemap::create(
        fn(): array => $this->itemRepository->findForPage($sitemapRequest->page),
        $this->itemRepository->findLastModified(),
        $this->itemRepository->countPages(),
    );

In case the extension supports both TYPO3 v14 and v15, extend
:php:`AbstractXmlSitemapDataProvider` instead of implementing the interface
directly: Such a data provider is instantiated with its runtime information in
v15 as well, at the cost of a deprecation message. It is then migrated to the
new interface once support for TYPO3 v14 is dropped.

..  index:: PHP-API, PartiallyScanned, ext:seo
