<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Dashboard\Widgets;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface as Cache;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Concrete RSS widget implementation
 *
 * The widget will show a certain number of items of the given RSS feed. The feed will be set by the feedUrl option. You
 * can add a button to the widget by defining a button provider.
 *
 * The following options are available during registration:
 * - feedUrl        string                      Defines the URL or file providing the RSS Feed.
 *                                              This is read by the widget in order to fetch entries to show.
 * - limit          int     default: 5          Defines how many RSS items should be shown.
 * - lifetime       int     default: 43200      Defines how long to wait, in seconds, until fetching RSS Feed again
 *
 * @see ButtonProviderInterface
 */
class RssWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    /**
     * @var array{limit: int, lifeTime: int, feedUrl?: string}
     */
    private readonly array $options;
    private ServerRequestInterface $request;

    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly Cache $cache,
        private readonly BackendViewFactory $backendViewFactory,
        // @deprecated since v12, will be removed in v13 together with services 'dashboard.views.widget' and Factory
        protected readonly ?StandaloneView $view = null,
        private readonly ?ButtonProviderInterface $buttonProvider = null,
        array $options = [],
    ) {
        $this->options = array_merge(
            [
                'limit' => 5,
                'lifeTime' => 43200,
            ],
            $options
        );
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $view = $this->backendViewFactory->create($this->request);
        $view->assignMultiple([
            'items' => $this->getRssItems(),
            'options' => $this->options,
            'button' => $this->buttonProvider,
            'configuration' => $this->configuration,
        ]);
        return $view->render('Widget/RssWidget');
    }

    protected function getRssItems(): array
    {
        if (empty($this->options['feedUrl'])) {
            return [];
        }
        $cacheHash = md5($this->options['feedUrl']);
        if ($items = $this->cache->get($cacheHash)) {
            return $items;
        }

        $rssContent = GeneralUtility::getUrl($this->options['feedUrl']);
        if ($rssContent === false) {
            throw new \RuntimeException('RSS URL could not be fetched', 1573385431);
        }
        $rssFeed = simplexml_load_string($rssContent);
        $items = [];
        foreach ($rssFeed->channel->item as $item) {
            $items[] = [
                'title' => trim((string)$item->title),
                'link' => trim((string)$item->link),
                'pubDate' => trim((string)$item->pubDate),
                'description' => trim(strip_tags((string)$item->description)),
            ];
        }
        usort($items, static function ($item1, $item2) {
            return new \DateTime($item2['pubDate']) <=> new \DateTime($item1['pubDate']);
        });
        $items = array_slice($items, 0, $this->options['limit']);

        $this->cache->set($cacheHash, $items, ['dashboard_rss'], $this->options['lifeTime']);

        return $items;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
