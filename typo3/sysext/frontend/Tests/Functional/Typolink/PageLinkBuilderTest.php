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

namespace TYPO3\CMS\Frontend\Tests\Functional\Typolink;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageLinkBuilderTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
    }

    #[Test]
    public function internalArgumentsAreAddedToLink(): void
    {
        // Add simple route enhancer to trigger routing mechanics
        $this->writeSiteConfiguration(
            'example',
            [
                'rootPageId' => 1,
                'base' => 'https://example.com/',
                'routeEnhancers' => [
                    'SimpleEnhancer' => [
                        'type' => 'Simple',
                        'routePath' => '/{name}',
                        '_arguments' => [
                            'name' => 'name',
                        ],
                    ],
                ],
            ],
        );

        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setPageRecord(['uid' => 1, 'pid' => 0, 'title' => 'Root']);

        // Create a minimal site
        $site = new Site('test', 1, [
            'base' => 'https://example.com/',
            'languages' => [
                ['languageId' => 0, 'title' => 'English', 'locale' => 'en_US.UTF-8', 'base' => 'https://example.com/'],
            ],
        ]);

        // Set up TypoScript configuration
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setConfigArray([]);

        // Add argument for simple enhancer and internal argument with double underscore.
        new PageArguments(1, '0', ['name' => 'benni', 'internal' => ['__argument' => 'foo']]);

        $request = (new ServerRequest('https://example.com'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('routing', new PageArguments(1, '0', ['name' => 'benni', 'internal' => ['__argument' => 'foo']]))
            ->withAttribute('site', $site);

        $pageLinkBuilder = $this->get(PageLinkBuilder::class);
        $result = $pageLinkBuilder->buildLink(
            [
                'pageuid' => 1,
            ],
            [
                'addQueryString' => 'untrusted',
            ],
            $request
        );
        // Remove cHash
        $url = $result->getUrl();
        $parts = explode('&cHash', $url);
        $resultWithoutCHash = $parts[0];
        $resultWithoutCHash = urldecode($resultWithoutCHash);

        self::assertSame('/benni?internal[__argument]=foo', $resultWithoutCHash);
    }
}
