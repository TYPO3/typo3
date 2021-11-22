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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Transform;

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class HtmlViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected $backupGlobals = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('EXT:core/Tests/Functional/Fixtures/pages.xml');
        $this->writeSiteConfiguration(
            'typo3-localhost',
            $this->buildSiteConfiguration(1, 'https://typo3.localhost/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );

        // A nullsite is used, so PageLinkBuilder does not "detect" the default site (from TSFE) as the same
        // site making all links absolute for our tests
        $rootPageSite = new NullSite();
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://typo3-2.localhost/', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('site', $rootPageSite)
            ->withAttribute('language', $rootPageSite->getDefaultLanguage());
    }

    public static function isTransformedDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                '',
            ],
            'any HTML tag' => [
                '<p>value a</p><p>value b</p>',
                '<p>value a</p><p>value b</p>',
            ],
            'unknown HTML tag' => [
                '<unknown>value</unknown>',
                '<unknown>value</unknown>',
            ],
            'empty' => [
                '<a href>visit</a>',
                '<a href>visit</a>',
            ],
            'invalid' => [
                '<a href="#">visit</a>',
                '<a href="#">visit</a>',
            ],
            'tel anchor' => [
                '<a href="tel:+123456789" class="phone voice">call</a>',
                '<a href="tel:+123456789" class="phone voice">call</a>',
            ],
            'mailto anchor' => [
                '<a href="mailto:test@typo3.localhost?subject=Test" class="mailto">send mail</a>',
                '<a href="mailto:test@typo3.localhost?subject=Test" class="mailto">send mail</a>',
            ],
            'https anchor' => [
                '<a href="https://typo3.localhost/path/visit.html" class="page">visit</a>',
                '<a href="https://typo3.localhost/path/visit.html" class="page">visit</a>',
            ],
            'absolute anchor' => [
                '<a href="/path/visit.html" class="page">visit</a>',
                '<a href="/path/visit.html" class="page">visit</a>',
            ],
            'relative anchor' => [
                '<a href="path/visit.html" class="page">visit</a>',
                '<a href="path/visit.html" class="page">visit</a>',
            ],
            't3-page anchor' => [
                '<a href="t3://page?uid=1" class="page">visit</a>',
                '<a href="https://typo3.localhost/" class="page">visit</a>',
            ],
            't3-page without uid anchor' => [
                '<a href="t3://page">visit</a>',
                '<a href="https://typo3.localhost/">visit</a>',
            ],
        ];
    }

    /**
     * @param string $payload
     * @param string $expectation
     * @test
     * @dataProvider isTransformedDataProvider
     */
    public function isTransformed(string $payload, string $expectation): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource(sprintf('<f:transform.html>%s</f:transform.html>', $payload));
        self::assertSame($expectation, $view->render());
    }

    public static function isTransformedWithSelectorDataProvider(): array
    {
        return [
            'a.href' => [
                'a.href',
                '<a href="t3://page?uid=1" class="page">visit</a>',
                '<a href="https://typo3.localhost/" class="page">visit</a>',
            ],
            '.href' => [
                '.href',
                '<a href="t3://page?uid=1" class="page">visit</a>',
                '<a href="https://typo3.localhost/" class="page">visit</a>',
            ],
            'div.data-uri' => [
                'div.data-uri',
                '<div data-uri="t3://page?uid=1" class="page">visit</div>',
                '<div data-uri="https://typo3.localhost/" class="page">visit</div>',
            ],
            'a.href,div.data-uri' => [
                'a.href,div.data-uri',
                '<a href="t3://page?uid=1">visit</a><div data-uri="t3://page?uid=1">visit</div>',
                '<a href="https://typo3.localhost/">visit</a><div data-uri="https://typo3.localhost/">visit</div>',
            ],
        ];
    }

    /**
     * @param string $selector
     * @param string $payload
     * @param string $expectation
     * @test
     * @dataProvider isTransformedWithSelectorDataProvider
     */
    public function isTransformedWithSelector(string $selector, string $payload, string $expectation): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource(sprintf('<f:transform.html selector="%s">%s</f:transform.html>', $selector, $payload));
        self::assertSame($expectation, $view->render());
    }

    public static function isTransformedWithOnFailureDataProvider(): array
    {
        return [
            't3-page invalid uid anchor (default)' => [
                null,
                '<a href="t3://page?uid=9876">visit</a>',
                'visit',
            ],
            't3-page invalid uid anchor ("removeEnclosure")' => [
                'removeEnclosure',
                '<a href="t3://page?uid=9876">visit</a>',
                'visit',
            ],
            't3-page invalid uid anchor ("removeTag")' => [
                'removeTag',
                '<a href="t3://page?uid=9876">visit</a>',
                '',
            ],
            't3-page invalid uid anchor ("removeAttr")' => [
                'removeAttr',
                '<a href="t3://page?uid=9876">visit</a>',
                '<a>visit</a>',
            ],
            't3-page invalid uid anchor ("null")' => [
                'null',
                '<a href="t3://page?uid=9876">visit</a>',
                '<a href="t3://page?uid=9876">visit</a>',
            ],
            't3-page invalid uid anchor ("")' => [
                '',
                '<a href="t3://page?uid=9876">visit</a>',
                '<a href="t3://page?uid=9876">visit</a>',
            ],
        ];
    }

    /**
     * @param string|null $onFailure
     * @param string $payload
     * @param string $expectation
     * @test
     * @dataProvider isTransformedWithOnFailureDataProvider
     */
    public function isTransformedWithOnFailure(?string $onFailure, string $payload, string $expectation): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource(sprintf(
            '<f:transform.html %s>%s</f:transform.html>',
            $onFailure !== null ? 'onFailure="' . $onFailure . '"' : '',
            $payload
        ));
        self::assertSame($expectation, $view->render());
    }
}
