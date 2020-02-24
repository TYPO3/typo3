<?php
declare(strict_types = 1);
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

use RuntimeException;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The AbstractRssWidget class is the basic widget class to display items from a RSS feed.
 * It is possible to extend this class for custom widgets.
 * In your class you have to set $this->rssFile with the URL to the feed.
 */
abstract class AbstractRssWidget extends AbstractListWidget
{
    /**
     * The rss file (resource) the data should be fetched from
     *
     * @var string
     */
    protected $rssFile = '';

    /**
     * Lifetime of the items cache
     *
     * @var int
     */
    protected $lifeTime = 900;

    /**
     * @inheritDoc
     */
    protected $height = 6;

    /**
     * @inheritDoc
     */
    protected $width = 4;

    /**
     * @inheritDoc
     */
    protected $iconIdentifier = 'content-widget-rss';

    /**
     * @inheritDoc
     */
    protected $templateName = 'RssWidget';

    /**
     * @var FrontendInterface
     */
    protected $cache;

    public function __construct(string $identifier)
    {
        AbstractListWidget::__construct($identifier);
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)
            ->getCache('dashboard_rss');
        $this->loadRssFeed();
    }

    protected function loadRssFeed(): void
    {
        $cacheHash = md5($this->rssFile);
        if ($this->items = $this->cache->get($cacheHash)) {
            return;
        }

        $rssContent = GeneralUtility::getUrl($this->rssFile);
        if ($rssContent === false) {
            throw new RuntimeException('RSS URL could not be fetched', 1573385431);
        }
        $rssFeed = simplexml_load_string($rssContent);
        $itemCount = 0;
        foreach ($rssFeed->channel->item as $item) {
            if ($itemCount < $this->limit) {
                $this->items[] = [
                    'title' => (string)$item->title,
                    'link' => trim((string)$item->link),
                    'pubDate' => (string)$item->pubDate,
                    'description' => (string)$item->description,
                ];
            } else {
                continue;
            }
            $itemCount++;
        }
        $this->cache->set($cacheHash, $this->items, ['dashboard_rss'], $this->lifeTime);
    }
}
