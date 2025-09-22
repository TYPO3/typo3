..  include:: /Includes.rst.txt

..  _feature-106363-1742136433:

===============================================================================
Feature: #106363 - PSR-14 event for modifying URLs for redirects:integritycheck
===============================================================================

See :issue:`106363`

Description
===========

A new PSR-14 event :php-short:`\TYPO3\CMS\Redirects\Event\AfterPageUrlsForSiteForRedirectIntegrityHaveBeenCollectedEvent`
is added which allows TYPO3 Extensions to register event listeners to modify
the list of URLs that are being processed by the CLI command
`redirects:checkintegrity <https://docs.typo3.org/permalink/typo3-cms-redirects:redirects-checkintegrity>`_.

Example
=======

The event listener class, using the PHP attribute :php:`#[AsEventListener]` for
registration, adds the URLs found in a sites XML sitemap to the list of URLs.

..  code-block:: php
    :caption: my_extension/Classes/EventListener/MyEventListener.php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Http\RequestFactory;
    use TYPO3\CMS\Redirects\Event\AfterPageUrlsForSiteForRedirectIntegrityHaveBeenCollectedEvent;

    final class MyEventListener
    {
        public function __construct(
            private RequestFactory $requestFactory,
        ) {}

        #[AsEventListener]
        public function __invoke(AfterPageUrlsForSiteForRedirectIntegrityHaveBeenCollectedEvent $event): void
        {
            $pageUrls = $event->getPageUrls();

            $additionalOptions = [
                'headers' => ['Cache-Control' => 'no-cache'],
                'allow_redirects' => false,
            ];

            $site = $event->getSite();
            foreach ($site->getLanguages() as $siteLanguage) {
                $sitemapIndexUrl = rtrim((string)$siteLanguage->getBase(), '/') . '/sitemap.xml';
                $response = $this->requestFactory->request(
                    $sitemapIndexUrl,
                    'GET',
                    $additionalOptions,
                );
                $sitemapIndex = simplexml_load_string($response->getBody()->getContents());
                foreach ($sitemapIndex as $sitemap) {
                    $sitemapUrl = (string)$sitemap->loc;
                    $response = $this->requestFactory->request(
                        $sitemapUrl,
                        'GET',
                        $additionalOptions,
                    );
                    $sitemap = simplexml_load_string($response->getBody()->getContents());
                    foreach ($sitemap as $url) {
                        $pageUrls[] = (string)$url->loc;
                    }
                }
            }

            $event->setPageUrls($pageUrls);
        }
    }

..  index:: PHP-API, ext:redirects
