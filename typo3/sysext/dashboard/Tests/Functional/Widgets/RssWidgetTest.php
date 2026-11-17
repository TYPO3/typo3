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

namespace TYPO3\CMS\Dashboard\Tests\Functional\Widgets;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use Psr\Log\AbstractLogger;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Factory\WidgetSettingsFactory;
use TYPO3\CMS\Dashboard\Widgets\RssWidget;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfiguration;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RssWidgetTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['dashboard'];

    /**
     * @var string[]
     */
    private array $requestedUrls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['feed'] = function () {
            return function (RequestInterface $request) {
                $this->requestedUrls[] = (string)$request->getUri();
                return new FulfilledPromise(
                    (new ResponseFactory())->createResponse()
                        ->withBody((new StreamFactory())->createStreamFromFile(__DIR__ . '/../Fixtures/feed.xml'))
                );
            };
        };
    }

    #[Test]
    public function feedOfConfiguredUrlIsFetched(): void
    {
        $result = $this->render(['feedUrl' => 'https://typo3.org/rss']);

        self::assertStringContainsString('Fixture item', $result);
        self::assertSame(['https://typo3.org/rss'], $this->requestedUrls);
    }

    #[Test]
    public function localFileOfConfiguredUrlIsNotRead(): void
    {
        $result = $this->render(['feedUrl' => __DIR__ . '/../Fixtures/feed.xml']);

        self::assertStringContainsString('Feed URL not supported', $result);
        self::assertStringNotContainsString('Fixture item', $result);
        self::assertSame([], $this->requestedUrls);
    }

    #[Test]
    public function localFileUrlOfConfiguredUrlIsNotRead(): void
    {
        $result = $this->render(['feedUrl' => 'file://' . realpath(__DIR__ . '/../Fixtures/feed.xml')]);

        self::assertStringContainsString('Feed URL not supported', $result);
        self::assertStringNotContainsString('Fixture item', $result);
        self::assertSame([], $this->requestedUrls);
    }

    public static function feedOfAnyHostIsFetchedWithoutAllowedHostsDataProvider(): array
    {
        return [
            'allowed hosts not configured' => [false],
            'allowed hosts set to null' => [true],
        ];
    }

    #[DataProvider('feedOfAnyHostIsFetchedWithoutAllowedHostsDataProvider')]
    #[Test]
    public function feedOfAnyHostIsFetchedWithoutAllowedHosts(bool $setNull): void
    {
        $this->setUpBackendUser(2);
        if ($setNull) {
            $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss'] = null;
        }

        $result = $this->render(['feedUrl' => 'http://127.0.0.1/feed.xml']);

        self::assertStringContainsString('Fixture item', $result);
        self::assertSame(['http://127.0.0.1/feed.xml'], $this->requestedUrls);
    }

    #[Test]
    public function malformedFeedUrlIsReportedAsFeedError(): void
    {
        $result = $this->render(['feedUrl' => 'http://exa mple.org/feed.xml']);

        self::assertStringContainsString('Feed error', $result);
        self::assertSame([], $this->requestedUrls);
    }

    #[Test]
    public function passwordOfFeedUrlIsNotLoggedOnFetchError(): void
    {
        $logger = new class extends AbstractLogger {
            public array $records = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = $context;
            }
        };
        $logManager = self::createStub(LogManager::class);
        $logManager->method('getLogger')->willReturn($logger);
        GeneralUtility::setSingletonInstance(LogManager::class, $logManager);
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']['feed'] = static fn() => static fn(RequestInterface $request) => new RejectedPromise(
            new ConnectException('Connection refused', $request)
        );

        $result = $this->render(['feedUrl' => 'https://john:secret@typo3.org/rss']);

        self::assertStringContainsString('Feed error', $result);
        self::assertSame(['https://***@typo3.org/rss'], array_column($logger->records, 'url'));
    }

    #[Test]
    public function feedOfConfiguredUrlRespectsAllowedHosts(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss'] = ['typo3.org'];

        $result = $this->render(['feedUrl' => 'http://127.0.0.1/feed.xml']);

        self::assertStringNotContainsString('Fixture item', $result);
        self::assertSame([], $this->requestedUrls);
    }

    #[Test]
    public function feedOfDisallowedHostIsReportedAsNotAllowed(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss'] = ['typo3.org'];

        $result = $this->render(['feedUrl' => 'https://example.org/feed.xml']);

        self::assertStringContainsString('Feed host not allowed', $result);
        self::assertStringNotContainsString('could not be parsed', $result);
        self::assertSame([], $this->requestedUrls);
    }

    #[Test]
    public function feedOfAllowedHostIsFetched(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss'] = ['*.example.org'];

        $result = $this->render(['feedUrl' => 'https://feeds.example.org/feed.xml']);

        self::assertStringContainsString('Fixture item', $result);
        self::assertSame(['https://feeds.example.org/feed.xml'], $this->requestedUrls);
    }

    #[Test]
    public function emptyAllowedHostsBlockAllFeeds(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['dashboard_rss'] = [];

        $result = $this->render(['feedUrl' => 'https://typo3.org/rss']);

        self::assertStringNotContainsString('Fixture item', $result);
        self::assertSame([], $this->requestedUrls);
    }

    #[Test]
    public function allowedHostsOfOtherContextsDoNotApply(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['HTTP']['allowed_hosts']['webhooks'] = [];

        $result = $this->render(['feedUrl' => 'https://typo3.org/rss']);

        self::assertStringContainsString('Fixture item', $result);
        self::assertSame(['https://typo3.org/rss'], $this->requestedUrls);
    }

    #[Test]
    public function feedUrlOfWidgetRegistrationCanNotBeOverriddenBySettings(): void
    {
        $result = $this->render(['feedUrl' => 'https://example.org/other.xml'], ['feedUrl' => 'https://typo3.org/rss']);

        self::assertStringContainsString('Fixture item', $result);
        self::assertSame(['https://typo3.org/rss'], $this->requestedUrls);
    }

    #[Test]
    public function localFileOfSettingsIsNotReadForFeedUrlOptionSetToNull(): void
    {
        $result = $this->render(['feedUrl' => __DIR__ . '/../Fixtures/feed.xml'], ['feedUrl' => null]);

        self::assertStringContainsString('Feed URL not supported', $result);
        self::assertStringNotContainsString('Fixture item', $result);
        self::assertSame([], $this->requestedUrls);
    }

    public static function localFileOfWidgetRegistrationIsNotReadDataProvider(): array
    {
        return [
            'file path' => [__DIR__ . '/../Fixtures/feed.xml'],
            'file url' => ['file://' . realpath(__DIR__ . '/../Fixtures/feed.xml')],
        ];
    }

    #[DataProvider('localFileOfWidgetRegistrationIsNotReadDataProvider')]
    #[Test]
    public function localFileOfWidgetRegistrationIsNotRead(string $feedFile): void
    {
        $result = $this->render(['feedUrl' => $feedFile], ['feedUrl' => $feedFile]);

        self::assertStringContainsString('Feed URL not supported', $result);
        self::assertStringNotContainsString('Fixture item', $result);
        self::assertSame([], $this->requestedUrls);
    }

    public static function feedUrlSettingIsValidatedDataProvider(): array
    {
        return [
            'http url' => ['http://example.org/feed.xml', 'http://example.org/feed.xml'],
            'https url' => ['https://example.org/feed.xml', 'https://example.org/feed.xml'],
            'file url' => ['file:///etc/passwd', ''],
            'url of other scheme containing http url' => ['php://filter/http://example.org/resource=/etc/passwd', ''],
        ];
    }

    #[DataProvider('feedUrlSettingIsValidatedDataProvider')]
    #[Test]
    public function feedUrlSettingIsValidated(string $feedUrl, string $expected): void
    {
        $subject = new RssWidget(
            new WidgetConfiguration('test', 'test', [], 'Test', 'Test', 'content-widget-rss', 'small', 'small'),
            new NullFrontend('dashboard_rss'),
            $this->get(BackendViewFactory::class),
        );

        $settings = $this->get(WidgetSettingsFactory::class)
            ->createSettings('rss', ['feedUrl' => $feedUrl], $subject->getSettingsDefinitions());

        self::assertSame($expected, $settings->get('feedUrl'));
    }

    private function render(array $settings, array $options = []): string
    {
        $normalizedParams = self::createStub(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $request = (new ServerRequest('https://example.com/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('route', new Route('path', ['packageName' => 'typo3/cms-dashboard']));
        $configuration = new WidgetConfiguration('test', 'test', [], 'Test', 'Test', 'content-widget-rss', 'small', 'small');
        $subject = new RssWidget(
            $configuration,
            new NullFrontend('dashboard_rss'),
            $this->get(BackendViewFactory::class),
            null,
            $options,
        );
        $settings += ['label' => '', 'limit' => 5, 'lifeTime' => 0];
        $context = new WidgetContext('test', [], $configuration, new Settings($settings), $request);
        return $subject->renderWidget($context)->content;
    }
}
