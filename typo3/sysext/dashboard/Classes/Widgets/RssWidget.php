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

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\RejectionException;
use GuzzleHttp\Psr7\Uri;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Exception\DisallowedRssFeedHostException;
use TYPO3\CMS\Dashboard\Exception\DisallowedRssFeedSchemeException;
use TYPO3\CMS\Dashboard\Exception\InvalidRssFeedException;

/**
 * Concrete RSS widget implementation
 *
 * The widget will show a certain number of items of the given RSS feed. The feed will be set by the feedUrl option. You
 * can add a button to the widget by defining a button provider.
 *
 * The following options are available during registration:
 * - feedUrl        string                      Defines the URL providing the RSS Feed.
 *                                              This is fetched by the widget in order to show its entries.
 * - limit          int     default: 5          Defines how many RSS items should be shown.
 * - lifetime       int     default: 43200      Defines how long to wait, in seconds, until fetching RSS Feed again
 *
 * @see ButtonProviderInterface
 */
readonly class RssWidget implements WidgetRendererInterface
{
    public function __construct(
        private WidgetConfigurationInterface $configuration,
        #[Autowire(service: 'cache.dashboard.rss')]
        private FrontendInterface $cache,
        private BackendViewFactory $backendViewFactory,
        private ?ButtonProviderInterface $buttonProvider = null,
        /** @var array{limit?: int, lifeTime?: int, feedUrl?: string} */
        private array $options = [],
    ) {}

    /**
     * @return SettingDefinition[]
     */
    public function getSettingsDefinitions(): array
    {
        return [
            new SettingDefinition(
                key: 'label',
                type: 'string',
                default: '',
                label: 'LLL:EXT:dashboard/Resources/Private/Language/locallang_widget_rss.xlf:widget.rss.setting.label.label',
                description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang_widget_rss.xlf:widget.rss.setting.label.description',
                readonly: $this->isFeedUrlDefinedByWidgetRegistration(),
            ),
            new SettingDefinition(
                key: 'feedUrl',
                type: 'url',
                default: (string)($this->options['feedUrl'] ?? ''),
                label: 'LLL:EXT:dashboard/Resources/Private/Language/locallang_widget_rss.xlf:widget.rss.setting.fieldUrl.label',
                description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang_widget_rss.xlf:widget.rss.setting.fieldUrl.description',
                readonly: $this->isFeedUrlDefinedByWidgetRegistration(),
                options: [
                    'pattern' => '^https?://.+',
                ],
            ),
            new SettingDefinition(
                key: 'limit',
                type: 'int',
                default: (int)($this->options['limit'] ?? 5),
                label: 'LLL:EXT:dashboard/Resources/Private/Language/locallang_widget_rss.xlf:widget.rss.setting.limit.label',
                description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang_widget_rss.xlf:widget.rss.setting.limit.description',
                readonly: array_key_exists('limit', $this->options),
            ),
            new SettingDefinition(
                key: 'lifeTime',
                type: 'int',
                default: (int)($this->options['lifeTime'] ?? 43200),
                label: 'LLL:EXT:dashboard/Resources/Private/Language/locallang_widget_rss.xlf:widget.rss.setting.lifeTime.label',
                description: 'LLL:EXT:dashboard/Resources/Private/Language/locallang_widget_rss.xlf:widget.rss.setting.lifeTime.description',
                readonly: true,
            ),
        ];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        $view = $this->backendViewFactory->create($context->request);
        $feedUrl = $this->getFeedUrl($context->settings);
        $items = [];
        if ($feedUrl) {
            try {
                $items = $this->getFeedItems($context->settings);
            } catch (DisallowedRssFeedHostException) {
                $view->assign('disallowedHost', true);
            } catch (DisallowedRssFeedSchemeException) {
                $view->assign('disallowedScheme', true);
            } catch (InvalidRssFeedException) {
                $view->assign('invalidFeed', true);
            }
        }
        $view->assignMultiple([
            'feedUrl' => $feedUrl,
            'items' => $items,
            'settings' => $context->settings,
            'options' => $this->options,
            'button' => $this->buttonProvider,
            'configuration' => $this->configuration,
        ]);
        return new WidgetResult(
            label: $context->settings->get('label') !== '' ? $context->settings->get('label') : null,
            content: $view->render('Widget/RssWidget'),
            refreshable: true,
        );
    }

    /**
     * @throws InvalidRssFeedException
     */
    protected function getFeedItems(SettingsInterface $settings): array
    {
        $feedUrl = $this->getFeedUrl($settings);
        $cacheHash = md5($feedUrl . '-' . $settings->get('limit'));
        if ($items = $this->cache->get($cacheHash)) {
            return $items;
        }

        $feedContent = $this->fetchFeedContent($feedUrl);
        try {
            $feedXml = simplexml_load_string($feedContent);
        } catch (\Exception $e) {
            throw new InvalidRssFeedException('Received RSS feed could not be parsed.', 1573385432, $e);
        }

        $items = match ($this->determineFeedType($feedXml)) {
            'atom' => $this->parseAtomFeed($feedXml),
            'rss' => $this->parseRssFeed($feedXml),
            default => []
        };

        usort($items, static function ($item1, $item2) {
            return new \DateTime($item2['pubDate']) <=> new \DateTime($item1['pubDate']);
        });
        $items = array_slice($items, 0, (int)$settings->get('limit'));

        $this->cache->set($cacheHash, $items, ['dashboard_rss'], (int)$settings->get('lifeTime'));

        return $items;
    }

    /**
     * A feed url of the widget registration is defined by developers, and is never
     * overridden by the settings of a widget instance.
     */
    protected function getFeedUrl(SettingsInterface $settings): string
    {
        return $this->isFeedUrlDefinedByWidgetRegistration() ? $this->options['feedUrl'] : (string)$settings->get('feedUrl');
    }

    protected function isFeedUrlDefinedByWidgetRegistration(): bool
    {
        return is_string($this->options['feedUrl'] ?? null);
    }

    /**
     * Feeds are fetched by http(s) only, the hosts can be restricted with
     * $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss'].
     *
     * @throws DisallowedRssFeedHostException
     * @throws DisallowedRssFeedSchemeException
     * @throws InvalidRssFeedException
     */
    protected function fetchFeedContent(string $feedUrl): string
    {
        if (!in_array(strtolower((string)parse_url($feedUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new DisallowedRssFeedSchemeException('Scheme of RSS URL is not allowed', 1794390041);
        }
        try {
            return GeneralUtility::makeInstance(RequestFactory::class)
                ->request($feedUrl, 'GET', [], 'dashboard_rss')
                ->getBody()
                ->getContents();
        } catch (RejectionException $e) {
            $this->logFetchError($feedUrl, $e);
            throw new DisallowedRssFeedHostException('Host of RSS URL is not allowed', 1793039663, $e);
        } catch (GuzzleException|\InvalidArgumentException $e) {
            $this->logFetchError($feedUrl, $e);
            throw new InvalidRssFeedException('RSS URL could not be fetched', 1573385431, $e);
        }
    }

    protected function logFetchError(string $feedUrl, \Throwable $exception): void
    {
        GeneralUtility::makeInstance(LogManager::class)
            ->getLogger(self::class)
            ->warning('Could not fetch feed content from {url}', ['url' => $this->redactFeedUrl($feedUrl), 'exception' => $exception]);
    }

    protected function redactFeedUrl(string $feedUrl): string
    {
        try {
            $uri = new Uri($feedUrl);
            return (string)($uri->getUserInfo() === '' ? $uri : $uri->withUserInfo('***'));
        } catch (\InvalidArgumentException) {
            return '(malformed URL)';
        }
    }

    protected function determineFeedType(\SimpleXMLElement $feedXml): string
    {
        return $feedXml->getName() === 'feed' ? 'atom' : 'rss';
    }

    protected function parseRssFeed(\SimpleXMLElement $rssFeed): array
    {
        $items = [];
        foreach ($rssFeed->channel->item as $item) {
            $items[] = [
                'title' => trim((string)$item->title),
                'link' => trim((string)$item->link),
                'pubDate' => trim((string)$item->pubDate),
                'description' => trim(strip_tags((string)$item->description)),
            ];
        }
        return $items;
    }

    protected function parseAtomFeed(\SimpleXMLElement $atomFeed): array
    {
        $items = [];
        foreach ($atomFeed->entry as $entry) {
            $items[] = [
                'title' => trim((string)$entry->title),
                'link' => trim((string)($entry->link['href'] ?? '')),
                'pubDate' => trim((string)($entry->published ?? $entry->updated ?? '')),
                'description' => trim(strip_tags((string)($entry->summary ?? $entry->content ?? ''))),
                'author' => [
                    'name' => trim((string)($entry->author->name ?? '')),
                    'email' => trim((string)($entry->author->email ?? '')),
                    'url' => trim((string)($entry->author->url ?? '')),
                ],
            ];
        }
        return $items;
    }
}
