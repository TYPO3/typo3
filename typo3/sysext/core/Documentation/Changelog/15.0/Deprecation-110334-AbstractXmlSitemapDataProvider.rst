..  include:: /Includes.rst.txt

..  _deprecation-110334-1785418299:

=====================================================
Deprecation: #110334 - AbstractXmlSitemapDataProvider
=====================================================

See :issue:`110334`

Description
===========

The class :php:`\TYPO3\CMS\Seo\XmlSitemap\AbstractXmlSitemapDataProvider` has
been marked as deprecated and will be removed in TYPO3 v16.0.

XML sitemap data providers are services now, receiving all runtime information
as :php:`\TYPO3\CMS\Seo\XmlSitemap\XmlSitemapRequest` argument of
:php:`getSitemap()`, see :ref:`breaking-110334-1785418299`. The base class only
serves the previous approach of handing over the runtime information as
constructor arguments and storing the items of a sitemap in a property.

The class is kept in TYPO3 v15 to ease the transition: Data providers extending
it are still instantiated with their runtime information and keep working
unchanged, which allows extensions to support TYPO3 v14 and v15 with a single
implementation.

Impact
======

Instantiating a data provider extending
:php:`AbstractXmlSitemapDataProvider` triggers a PHP :php:`E_USER_DEPRECATED`
error. The sitemap itself is rendered as before.

The extension scanner detects usages of the deprecated class as strong match.

Affected installations
======================

All installations with custom extensions providing an own XML sitemap data
provider based on :php:`AbstractXmlSitemapDataProvider`.

Migration
=========

Implement :php:`\TYPO3\CMS\Seo\XmlSitemap\XmlSitemapDataProviderInterface`
directly instead of extending the base class. The items are collected in
:php:`getSitemap()` instead of the constructor, and the properties of the base
class are replaced by the :php:`XmlSitemapRequest` argument:

..  code-block:: php
    :caption: EXT:my_extension/Classes/XmlSitemap/MyXmlSitemapDataProvider.php

    // Before
    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Core\Utility\GeneralUtility;
    use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
    use TYPO3\CMS\Seo\XmlSitemap\AbstractXmlSitemapDataProvider;

    class MyXmlSitemapDataProvider extends AbstractXmlSitemapDataProvider
    {
        public function __construct(ServerRequestInterface $request, string $key, array $config = [], ?ContentObjectRenderer $cObj = null)
        {
            parent::__construct($request, $key, $config, $cObj);
            $itemRepository = GeneralUtility::makeInstance(MyItemRepository::class);
            foreach ($itemRepository->findAll($this->config) as $item) {
                $this->items[] = [
                    'uid' => $item['uid'],
                    'lastMod' => $item['tstamp'],
                ];
            }
        }

        protected function defineUrl(array $data): array
        {
            $data['loc'] = $this->cObj->createUrl([
                'parameter' => $data['uid'],
                'forceAbsoluteUrl' => 1,
            ]);
            return $data;
        }
    }

..  code-block:: php
    :caption: EXT:my_extension/Classes/XmlSitemap/MyXmlSitemapDataProvider.php

    // After
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

The properties of the base class are mapped as follows:

-   :php:`$this->key` becomes :php:`$sitemapRequest->name`
-   :php:`$this->config` becomes :php:`$sitemapRequest->configuration`
-   :php:`$this->request` becomes :php:`$sitemapRequest->request`
-   :php:`$this->cObj` becomes :php:`$sitemapRequest->contentObjectRenderer`
-   :php:`$this->items` becomes the items handed over to
    :php:`XmlSitemap::forPage()`
-   :php:`$this->numberOfItemsPerPage` becomes the fourth argument of
    :php:`XmlSitemap::forPage()`

The methods :php:`getKey()`, :php:`getItems()`, :php:`getLastModified()` and
:php:`getNumberOfPages()` are not needed anymore: The returned
:php:`XmlSitemap` provides the last modification date and the number of pages,
both calculated from the items of the sitemap.

Registering a sitemap in TypoScript is unchanged: Data providers are still
referenced by their class name.

Extensions supporting TYPO3 v14 and v15 with a single implementation keep
extending :php:`AbstractXmlSitemapDataProvider` and migrate once support for
TYPO3 v14 is dropped.

..  index:: PHP-API, FullyScanned, ext:seo
