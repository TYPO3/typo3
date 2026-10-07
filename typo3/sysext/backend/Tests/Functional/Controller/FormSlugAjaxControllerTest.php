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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\FormSlugAjaxController;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Routing\Event\AfterPageUriGeneratedEvent;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FormSlugAjaxControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    public static function suggestActionReturnsPreviewPathDataProvider(): \Generator
    {
        $pageTypeSuffix = [
            'PageTypeSuffix' => [
                'type' => 'PageType',
                'default' => '.html',
                'map' => ['.html' => 0, 'feed.xml' => 100],
            ],
        ];
        yield 'without route enhancers' => [
            [],
            0,
            '/unsere-services',
            '/unsere-services',
        ];
        yield 'with page type suffix' => [
            $pageTypeSuffix,
            0,
            '/unsere-services',
            '/unsere-services.html',
        ];
        yield 'with page type suffix in a translation' => [
            $pageTypeSuffix,
            1,
            '/',
            '/index.html',
            100,
            0,
        ];
        yield 'with page type suffix on a slug ending with a slash' => [
            $pageTypeSuffix,
            0,
            '/unsere-services/',
            '/unsere-services.html',
        ];
        yield 'with page type suffix on the root page' => [
            $pageTypeSuffix,
            0,
            '/',
            '/index.html',
            1,
            0,
        ];
        yield 'with page type suffix on a new page' => [
            $pageTypeSuffix,
            0,
            '/unsere-services',
            '/unsere-services.html',
            0,
            2,
        ];
        yield 'with page type suffix limited to another page' => [
            ['PageTypeSuffix' => array_replace($pageTypeSuffix['PageTypeSuffix'], ['limitToPages' => [5]])],
            0,
            '/unsere-services',
            '/unsere-services',
        ];
        yield 'with page type suffix limited to the edited page' => [
            ['PageTypeSuffix' => array_replace($pageTypeSuffix['PageTypeSuffix'], ['limitToPages' => [2]])],
            0,
            '/unsere-services',
            '/unsere-services.html',
        ];
        yield 'with page type suffix limited by an expression on the edited page' => [
            ['PageTypeSuffix' => array_replace($pageTypeSuffix['PageTypeSuffix'], ['limitToPages' => ['page["title"] == "Dummy 1-2"']])],
            0,
            '/unsere-services',
            '/unsere-services.html',
        ];
        yield 'with page type suffix limited by an expression on the translated page' => [
            ['PageTypeSuffix' => array_replace($pageTypeSuffix['PageTypeSuffix'], ['limitToPages' => ['page["title"] == "Wurzel" and page["_LOCALIZED_UID"] == 100']])],
            1,
            '/',
            '/index.html',
            100,
            0,
        ];
        yield 'with page type suffix limited by an expression on the default language page' => [
            ['PageTypeSuffix' => array_replace($pageTypeSuffix['PageTypeSuffix'], ['limitToPages' => ['page["title"] == "Root"']])],
            1,
            '/',
            '/',
            100,
            0,
        ];
        yield 'with page type suffix limited by an expression on a new page' => [
            ['PageTypeSuffix' => array_replace($pageTypeSuffix['PageTypeSuffix'], ['limitToPages' => ['page["title"] == "Unsere Services"']])],
            0,
            '/unsere-services',
            '/unsere-services.html',
            0,
            2,
            ['title' => 'Unsere Services'],
        ];
    }

    #[Test]
    #[DataProvider('suggestActionReturnsPreviewPathDataProvider')]
    public function suggestActionReturnsPreviewPath(array $routeEnhancers, int $languageId, string $slug, string $expectedPreviewPath, int $recordId = 2, int $parentPageId = 1, array $values = []): void
    {
        $siteConfiguration = $this->buildSiteConfiguration(1, 'http://localhost/');
        if ($routeEnhancers !== []) {
            $siteConfiguration['routeEnhancers'] = $routeEnhancers;
        }
        $this->writeSiteConfiguration(
            'website-local',
            $siteConfiguration,
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/'),
            ]
        );

        self::assertSame($expectedPreviewPath, $this->requestPreviewPath($languageId, $slug, $recordId, $parentPageId, $values));
    }

    #[Test]
    public function suggestActionReturnsPreviewPathBelowLanguageBaseWithEncodedCharacters(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/über/')]
        );

        self::assertSame('/unsere-services', $this->requestPreviewPath(0, '/unsere-services'));
    }

    #[Test]
    public function suggestActionReturnsSlugAsPreviewPathIfUriGenerationFails(): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );
        $container = $this->get('service_container');
        $container->set(
            'failing-after-page-uri-generated-listener',
            static function (AfterPageUriGeneratedEvent $event): void {
                throw new \RuntimeException('Listener failed', 1791382946);
            }
        );
        $container->get(ListenerProvider::class)->addListener(AfterPageUriGeneratedEvent::class, 'failing-after-page-uri-generated-listener');

        self::assertSame('/unsere-services', $this->requestPreviewPath(0, '/unsere-services'));
    }

    #[Test]
    public function suggestActionReturnsPreviewPathOfWorkspaceVersion(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FormSlugAjaxControllerWorkspace.csv');
        $GLOBALS['BE_USER']->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
        $siteConfiguration = $this->buildSiteConfiguration(1, 'http://localhost/');
        $siteConfiguration['routeEnhancers'] = [
            'PageTypeSuffix' => [
                'type' => 'PageType',
                'default' => '.html',
                'map' => ['.html' => 0],
                'limitToPages' => ['page["title"] == "Dummy 1-2 in workspace"'],
            ],
        ];
        $this->writeSiteConfiguration(
            'website-local',
            $siteConfiguration,
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        self::assertSame('/unsere-services.html', $this->requestPreviewPath(0, '/unsere-services'));
    }

    private function requestPreviewPath(int $languageId, string $slug, int $recordId = 2, int $parentPageId = 1, array $values = []): string
    {
        $parameters = [
            'tableName' => 'pages',
            'pageId' => (string)($recordId ?: $parentPageId),
            'recordId' => (string)$recordId,
            'language' => (string)$languageId,
            'fieldName' => 'slug',
            'command' => $recordId > 0 ? 'edit' : 'new',
            'parentPageId' => (string)$parentPageId,
        ];
        $parameters['signature'] = $this->get(HashService::class)->hmac(implode('', $parameters), FormSlugAjaxController::class);
        $parameters['mode'] = 'manual';
        $parameters['values'] = array_replace($values, ['manual' => $slug]);
        $request = (new ServerRequest('http://localhost/typo3/', 'POST'))->withParsedBody($parameters);

        $response = $this->get(FormSlugAjaxController::class)->suggestAction($request);

        return json_decode((string)$response->getBody(), true)['previewPath'];
    }
}
