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
use TYPO3\CMS\Backend\Controller\LinkPreviewController;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LinkPreviewControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_link_preview.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages_link_preview.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'https://example.com/'),
        );
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function pageLinkIsResolvedToTitlePathAndFrontendUrl(): void
    {
        $result = $this->resolve('t3://page?uid=3');

        self::assertSame('page', $result['type']);
        self::assertSame('Team', $result['title']);
        self::assertSame('/Home/About us/Team/', $result['path']);
        self::assertSame('https://example.com/about-us/team', $result['url']);
    }

    #[Test]
    public function pageLinkKeepsFragmentAndParametersInFrontendUrl(): void
    {
        $result = $this->resolve('t3://page?uid=2&foo=bar#c13');

        self::assertSame('About us', $result['title']);
        self::assertStringStartsWith('https://example.com/about-us?foo=bar', $result['url']);
        self::assertStringEndsWith('#c13', $result['url']);
    }

    #[Test]
    public function unknownPageIsReportedAsNotFound(): void
    {
        $result = $this->resolve('t3://page?uid=999');

        self::assertSame('page', $result['type']);
        self::assertNull($result['title']);
        self::assertNull($result['url']);
    }

    public static function externalLinksAreReturnedAsIsDataProvider(): array
    {
        return [
            'url' => ['https://typo3.org/', 'https://typo3.org/', 'https://typo3.org/'],
            'mail' => ['mailto:info@example.com', 'info@example.com', 'mailto:info@example.com'],
            'telephone' => ['tel:+4912345', '+4912345', 'tel:+4912345'],
        ];
    }

    #[Test]
    #[DataProvider('externalLinksAreReturnedAsIsDataProvider')]
    public function externalLinksAreReturnedAsIs(string $href, string $expectedTitle, string $expectedUrl): void
    {
        $result = $this->resolve($href);

        self::assertSame($expectedTitle, $result['title']);
        self::assertSame($expectedUrl, $result['url']);
    }

    #[Test]
    public function unresolvableLinkYieldsEmptyResult(): void
    {
        $result = $this->resolve('t3://doesnotexist?uid=1');

        self::assertNull($result['type']);
        self::assertNull($result['title']);
        self::assertNull($result['url']);
    }

    public static function malformedLinksYieldEmptyResultDataProvider(): array
    {
        return [
            'array as url' => ['t3://url?url[]=x'],
            'array as email' => ['t3://email?email[]=x'],
            'folder with path traversal' => ['t3://folder?storage=1&identifier=/../etc/'],
        ];
    }

    #[Test]
    #[DataProvider('malformedLinksYieldEmptyResultDataProvider')]
    public function malformedLinkYieldsEmptyResult(string $href): void
    {
        $result = $this->resolve($href);

        self::assertNull($result['title']);
        self::assertNull($result['url']);
    }

    #[Test]
    public function recordLinkIsNotResolved(): void
    {
        $result = $this->resolve('t3://record?identifier=pages&uid=3');

        self::assertNull($result['title']);
        self::assertNull($result['path']);
        self::assertNull($result['url']);
    }

    #[Test]
    public function pageWithoutReadAccessIsNotDisclosed(): void
    {
        $this->setUpBackendUser(2);
        $result = $this->resolve('t3://page?uid=3');

        self::assertNull($result['title']);
        self::assertNull($result['path']);
    }

    private function resolve(string $href): array
    {
        $request = new ServerRequest('https://example.com/typo3/ajax/link/preview')
            ->withQueryParams(['href' => $href]);
        $response = $this->get(LinkPreviewController::class)->resolveAction($request);
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
