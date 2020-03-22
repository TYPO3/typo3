<?php
declare(strict_types=1);
namespace TYPO3\CMS\Dashboard\Widgets;

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

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface as Cache;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\Interfaces\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\Interfaces\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\Interfaces\WidgetInterface;
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
class RssWidget implements WidgetInterface
{
    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;

    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var array
     */
    private $options;

    /**
     * @var ButtonProviderInterface|null
     */
    private $buttonProvider;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        Cache $cache,
        StandaloneView $view,
        $buttonProvider = null,
        array $options = []
    ) {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->cache = $cache;
        $this->options = array_merge(
            [
            'limit' => 5,
            'lifeTime' => 43200
            ],
            $options
        );
        $this->buttonProvider = $buttonProvider;
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('Widget/RssWidget');
        $this->view->assignMultiple([
            'items' => $this->getRssItems(),
            'options' => $this->options,
            'button' => $this->getButton(),
            'configuration' => $this->configuration,
        ]);
        return $this->view->render();
    }

    protected function getRssItems(): array
    {
        $cacheHash = md5($this->options['feedUrl']);
        if ($items = $this->cache->get($cacheHash)) {
            return $items;
        }

        $rssContent = GeneralUtility::getUrl($this->options['feedUrl']);
        if ($rssContent === false) {
            throw new \RuntimeException('RSS URL could not be fetched', 1573385431);
        }
        $rssFeed = simplexml_load_string($rssContent);
        $itemCount = 0;
        $items = [];
        foreach ($rssFeed->channel->item as $item) {
            if ($itemCount >= $this->options['limit']) {
                break;
            }

            $items[] = [
                'title' => (string)$item->title,
                'link' => trim((string)$item->link),
                'pubDate' => (string)$item->pubDate,
                'description' => (string)$item->description,
            ];
            $itemCount++;
        }
        $this->cache->set($cacheHash, $items, ['dashboard_rss'], $this->options['lifeTime']);

        return $items;
    }

    private function getButton(): array
    {
        $button = [];

        if ($this->buttonProvider instanceof ButtonProviderInterface && $this->buttonProvider->getTitle() && $this->buttonProvider->getLink()) {
            $button = [
                'text' => $this->buttonProvider->getTitle(),
                'link' => $this->buttonProvider->getLink(),
                'target' => $this->buttonProvider->getTarget(),
            ];
        }

        return $button;
    }
}
