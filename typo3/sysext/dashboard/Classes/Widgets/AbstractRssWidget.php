<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Dashboard\Widgets;

use RuntimeException;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The AbstractRssWidget class is the basic widget class to display items from a RSS feed.
 * Is it possible to extends this class for own widgets.
 * In your class you have to set $this->rssFile with the URL to the feed.
 */
abstract class AbstractRssWidget extends AbstractListWidget
{
    protected $rssFile = '';

    protected $lifeTime = 900;

    protected $iconIdentifier = 'dashboard-rss';

    protected $templateName = 'RssWidget';

    protected $height = 6;
    protected $width = 4;

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

    public function setRssFile(string $rssFile): void
    {
        $this->rssFile = $rssFile;
    }

    protected function loadRssFeed(): void
    {
        $cacheHash = md5($this->rssFile);
        if ($this->items = $this->cache->get($cacheHash)) {
            return;
        }

        /** @var \SimpleXMLElement $rssFeed */
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
