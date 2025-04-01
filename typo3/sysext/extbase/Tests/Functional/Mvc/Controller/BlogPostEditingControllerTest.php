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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\BlogExample\Domain\Repository\BlogRepository;

final class BlogPostEditingControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;
    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];
    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'encryptionKey' => '1234123412341234123412341234123412341234123412341234123412341234',
        ],
        'FE' => [
            'debug' => true,
        ],
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private function setUpFrontendRootPageForTestCase(): void
    {
        $this->setUpFrontendRootPage(
            1,
            [
                'EXT:extbase/Tests/Functional/Fixtures/Extensions/blog_example/Configuration/TypoScript/setup.typoscript',
                'EXT:extbase/Tests/Functional/Fixtures/Extensions/blog_example/Configuration/TypoScript/Frontend/setup.typoscript',
                'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
            ]
        );
    }

    #[Test]
    public function fullyLocalizedFormSubmissionPersistsDataOfSelectedBlogForRequestedTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixture/BlogPostEditingData.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ]
        );

        $postLink = '/de/home';
        $args = [
            'id' => 3,
            'tx_blogexample_blogpostediting' => [
                'action' => 'persist',
                'controller' => 'BlogPostEditing',
            ],
        ];
        $args = $this->enrichArgumentsWithChash($args);
        $postPayload = [
            'tx_blogexample_blogpostediting' => [
                'blog' => [
                    '__identity' => '1',
                    'title' => 'Blog 1 EN UPDATED', // updated
                    'categories' => [
                        0 => 1,
                        1 => 5,
                        // removed: 7
                    ],
                ],
                '__referrer' => [
                    '@extension' => 'BlogExample',
                    '@controller' => 'BlogPostEditing',
                    '@action' => 'edit',
                    'arguments' => 'YTozOntzOjY6ImFjdGlvbiI7czo0OiJlZGl0IjtzOjQ6ImJsb2ciO3M6MToiMSI7czoxMDoiY29udHJvbGxlciI7czoxNToiQmxvZ1Bvc3RFZGl0aW5nIjt99ed507271464fbec158ce0628d6f8855f895f144',
                    '@request' => '{"@extension":"BlogExample","@controller":"BlogPostEditing","@action":"edit"}4d9253a8ab4cef10413988c74786fdd79e33d8cb',
                ],
                '__trustedProperties' => '{"blog":{"title":1,"categories":[1,1,1,1],"__identity":1},"submit":1}e97f2e81f4d7495dcf02d833db3bb407645755a4',
                //this variant with ":1" instead of ":[1,1,1,1]" does not work
                //'__trustedProperties' => '{"blog":{"title":1,"categories":1,"__identity":1},"submit":1}e446d223c1caf949a45b4eb08744955b00e3741a',
            ],
        ];
        $requestContext = new InternalRequestContext();

        $request = (new InternalRequest('https://www.acme.com' . $postLink))
            ->withMethod('POST')
            ->withQueryParams($args)
            ->withParsedBody($postPayload)
            ->withBody($this->createBodyFromArray($postPayload))
            ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded');

        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        $blogRepository = $this->get(BlogRepository::class);
        $blog = $blogRepository->findByUid(1);
        self::assertSame('Blog 1 EN UPDATED', $blog->getTitle());
        $categoryUids = [];
        foreach ($blog->getCategories() as $category) {
            $categoryUids[] = $category->getUid();
        }
        self::assertSame([1, 5], $categoryUids);
    }

    /**
     * @todo move this helper method to TF?
     */
    protected function createBodyFromArray(array $postPayload): StreamInterface
    {
        $streamFactory = $this->get(StreamFactoryInterface::class);
        return $streamFactory->createStream(HttpUtility::buildQueryString($postPayload));
    }

    #[Test]
    public function fullyLocalizedListDisplaysBlogsForRequestedDefaultLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixture/BlogPostEditingData.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        self::assertStringContainsString('<div id="c1"', $content);
        self::assertStringContainsString('Intro for EN', $content);
        self::assertStringContainsString('<li><span class="blogtitle">Blog2</span>', $content);
        self::assertStringContainsString('<li><span class="blogtitle">Blog1 EN</span>', $content);
        self::assertStringContainsString('<li>Category 1 (English)</li>', $content);
        self::assertStringContainsString('<li>Category 4 (English)</li>', $content);
        self::assertStringContainsString('<li>Category 5 (English)</li>', $content);
    }

    #[Test]
    public function fullyLocalizedListDisplaysBlogsForRequestedGermanLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixture/BlogPostEditingData.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ]
        );

        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com/de/home');
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        $content = (string)$response->getBody();
        self::assertStringContainsString('<a id="c2"></a>', $content);
        self::assertStringContainsString('Intro for DE', $content);
        self::assertStringContainsString('<li><span class="blogtitle">Blog3</span>', $content);
        self::assertStringContainsString('<li><span class="blogtitle">Blog1 DE</span>', $content);
        self::assertStringContainsString("<li>Category 1 (German)</li>\n", $content);
        self::assertStringContainsString("<li>Category 4 (German)</li>\n", $content);
    }

    #[Test]
    public function fullyLocalizedDetailDisplaysSelectedBlogForRequestedDefaultLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixture/BlogPostEditingData.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $args = [
            'id' => 3,
            'tx_blogexample_blogpostediting[action]' => 'view',
            'tx_blogexample_blogpostediting[blog]' => 3,
            'tx_blogexample_blogpostediting[controller]' => 'BlogPostEditing',
        ];
        $args = $this->enrichArgumentsWithChash($args);
        $detailLink = '/home?' . HttpUtility::buildQueryString($args);
        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com' . $detailLink);
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        // Evaluate single view
        $content = (string)$response->getBody();
        self::assertStringContainsString('<h2>Blog2</h2>', $content);
        self::assertStringContainsString('<p>#3</p>', $content);
    }

    #[Test]
    public function fullyLocalizedDetailDisplaysSelectedBlogForRequestedTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixture/BlogPostEditingData.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ]
        );

        $args = [
            'id' => 3,
            'tx_blogexample_blogpostediting[action]' => 'view',
            'tx_blogexample_blogpostediting[blog]' => 4,
            'tx_blogexample_blogpostediting[controller]' => 'BlogPostEditing',
        ];
        $args = $this->enrichArgumentsWithChash($args);
        $detailLink = '/de/home?' . HttpUtility::buildQueryString($args);
        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com' . $detailLink);
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        // Evaluate single view
        $content = (string)$response->getBody();
        self::assertStringContainsString('<h2>Blog3</h2>', $content);
        self::assertStringContainsString('<p>#4</p>', $content);
    }

    #[Test]
    public function fullyLocalizedFormEditDisplaysSelectedBlogForRequestedDefaultLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixture/BlogPostEditingData.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $args = [
            'id' => 3,
            'tx_blogexample_blogpostediting[action]' => 'edit',
            'tx_blogexample_blogpostediting[blog]' => 1,
            'tx_blogexample_blogpostediting[controller]' => 'BlogPostEditing',
        ];
        $args = $this->enrichArgumentsWithChash($args);
        $detailLink = '/home?' . HttpUtility::buildQueryString($args);
        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com' . $detailLink);
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        // Evaluate edit view
        $content = (string)$response->getBody();

        // Ensure basic extbase f:form handling
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[blog][__identity]" value="1"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__referrer][@extension]" value="BlogExample"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__referrer][@controller]" value="BlogPostEditing"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__referrer][@action]" value="edit"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__referrer][arguments]" value="YTozOntzOjY6ImFjdGlvbiI7czo0OiJlZGl0IjtzOjQ6ImJsb2ciO3M6MToiMSI7czoxMDoiY29udHJvbGxlciI7czoxNToiQmxvZ1Bvc3RFZGl0aW5nIjt99ed507271464fbec158ce0628d6f8855f895f144"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__referrer][@request]" value="{&quot;@extension&quot;:&quot;BlogExample&quot;,&quot;@controller&quot;:&quot;BlogPostEditing&quot;,&quot;@action&quot;:&quot;edit&quot;}4d9253a8ab4cef10413988c74786fdd79e33d8cb"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__trustedProperties]" value="{&quot;blog&quot;:{&quot;title&quot;:1,&quot;categories&quot;:[1,1,1,1],&quot;__identity&quot;:1},&quot;submit&quot;:1}e97f2e81f4d7495dcf02d833db3bb407645755a4"', $content);

        // Ensure f:form.textfield
        self::assertStringContainsString('<input id="persist-title" type="text" name="tx_blogexample_blogpostediting[blog][title]" value="Blog1 EN" required="required" />', $content);

        // Ensure assigned categories
        self::assertMatchesRegularExpression('@<ul class="available-categories-list">\s*'
            . '<li>#1 - Category 1 \(English\)</li>\s*'
            . '<li>#3 - Category 2 \(Only english\)</li>\s*'
            . '<li>#5 - Category 4 \(English\)</li>\s*'
            . '<li>#7 - Category 5 \(English\)</li>\s*'
            . '</ul>@', $content);
        self::assertMatchesRegularExpression('@<ul class="set-categories-list">\s*'
            . '<li>#1 - Category 1 \(English\)</li>\s*'
            . '<li>#5 - Category 4 \(English\)</li>\s*'
            . '<li>#7 - Category 5 \(English\)</li>\s*'
            . '</ul>@', $content);

        // Ensure f:form.select
        self::assertMatchesRegularExpression('@<input type="hidden" name="tx_blogexample_blogpostediting\[blog\]\[categories\]" value="" /><select id="persist-categories" multiple="multiple" name="tx_blogexample_blogpostediting\[blog\]\[categories\]\[\]">'
            . '<option value="1" selected="selected">Category 1 \(English\)</option>\s*'
            . '<option value="3">Category 2 \(Only english\)</option>\s*'
            . '<option value="5" selected="selected">Category 4 \(English\)</option>\s*'
            . '<option value="7" selected="selected">Category 5 \(English\)</option>\s*'
            . '</select>@', $content);
    }

    #[Test]
    public function fullyLocalizedFormEditDisplaysSelectedBlogForRequestedTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixture/BlogPostEditingData.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ]
        );

        $args = [
            'id' => 3,
            'tx_blogexample_blogpostediting[action]' => 'edit',
            'tx_blogexample_blogpostediting[blog]' => 1,
            'tx_blogexample_blogpostediting[controller]' => 'BlogPostEditing',
        ];
        $args = $this->enrichArgumentsWithChash($args);
        $detailLink = '/de/home?' . HttpUtility::buildQueryString($args);
        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com' . $detailLink);
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        // Evaluate edit view
        $content = (string)$response->getBody();

        // Ensure basic extbase f:form handling
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[blog][__identity]" value="1"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__referrer][@extension]" value="BlogExample"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__referrer][@controller]" value="BlogPostEditing"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__referrer][@action]" value="edit"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__referrer][arguments]" value="YTozOntzOjY6ImFjdGlvbiI7czo0OiJlZGl0IjtzOjQ6ImJsb2ciO3M6MToiMSI7czoxMDoiY29udHJvbGxlciI7czoxNToiQmxvZ1Bvc3RFZGl0aW5nIjt99ed507271464fbec158ce0628d6f8855f895f144"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__referrer][@request]" value="{&quot;@extension&quot;:&quot;BlogExample&quot;,&quot;@controller&quot;:&quot;BlogPostEditing&quot;,&quot;@action&quot;:&quot;edit&quot;}4d9253a8ab4cef10413988c74786fdd79e33d8cb"', $content);
        self::assertStringContainsString('<input type="hidden" name="tx_blogexample_blogpostediting[__trustedProperties]" value="{&quot;blog&quot;:{&quot;title&quot;:1,&quot;categories&quot;:[1,1,1,1],&quot;__identity&quot;:1},&quot;submit&quot;:1}e97f2e81f4d7495dcf02d833db3bb407645755a4"', $content);

        // Ensure f:form.textfield
        self::assertStringContainsString('<input id="persist-title" type="text" name="tx_blogexample_blogpostediting[blog][title]" value="Blog1 DE" required="required" />', $content);

        // Ensure assigned categories
        self::assertMatchesRegularExpression('@<ul class="available-categories-list">\s*'
            . '<li>#1 - Category 1 \(German\)</li>\s*'
            . '<li>#4 - Category 3 \(Only german\)</li>\s*'
            . '<li>#5 - Category 4 \(German\)</li>\s*'
            . '<li>#7 - Category 5 \(German\)</li>\s*'
            . '</ul>@', $content);
        self::assertMatchesRegularExpression('@<ul class="set-categories-list">\s*'
            . '<li>#1 - Category 1 \(German\)</li>\s*'
            . '<li>#5 - Category 4 \(German\)</li>\s*'
            . '<li>#7 - Category 5 \(German\)</li>\s*'
            . '</ul>@', $content);

        self::assertMatchesRegularExpression('@<input type="hidden" name="tx_blogexample_blogpostediting\[blog\]\[categories\]" value="" /><select id="persist-categories" multiple="multiple" name="tx_blogexample_blogpostediting\[blog\]\[categories\]\[\]">'
            . '<option value="1" selected="selected">Category 1 \(German\)</option>\s*'
            . '<option value="4">Category 3 \(Only german\)</option>\s*'
            . '<option value="5" selected="selected">Category 4 \(German\)</option>\s*'
            . '<option value="7" selected="selected">Category 5 \(German\)</option>\s*'
            . '</select>@', $content);
    }

    #[Test]
    public function createANewBlogRecordThroughExtbaseShowsProperEditingForm(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixture/BlogPostEditingData.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ]
        );

        $args = [
            'id' => 3,
            'tx_blogexample_blogpostediting[action]' => 'new',
            'tx_blogexample_blogpostediting[controller]' => 'BlogPostEditing',
        ];
        $args = $this->enrichArgumentsWithChash($args);
        $detailLink = '/de/home?' . HttpUtility::buildQueryString($args);
        $requestContext = new InternalRequestContext();
        $request = new InternalRequest('https://www.acme.com' . $detailLink);
        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        // Evaluate edit view
        $content = (string)$response->getBody();

        self::assertStringContainsString('<input id="persist-title" type="text" name="tx_blogexample_blogpostediting[blog][title]" value="" required="required" />', $content);

        // Also currently contains "wrong" identity assumptions:
        self::assertMatchesRegularExpression('@<input type="hidden" name="tx_blogexample_blogpostediting\[blog\]\[categories\]" value="" /><select id="persist-categories" multiple="multiple" name="tx_blogexample_blogpostediting\[blog\]\[categories\]\[\]">'
            . '<option value="1_2">TYPO3Tests\\\BlogExample\\\Domain\\\Model\\\Category:1</option>\s*'
            . '<option value="4">TYPO3Tests\\\BlogExample\\\Domain\\\Model\\\Category:4</option>\s*'
            . '<option value="5_6">TYPO3Tests\\\BlogExample\\\Domain\\\Model\\\Category:5</option>\s*'
            . '<option value="7_8">TYPO3Tests\\\BlogExample\\\Domain\\\Model\\\Category:7</option>\s*'
            . '</select>@', $content);
    }

    #[Test]
    public function addingANewBlogRecordThroughExtbaseCreatesRecord(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixture/BlogPostEditingData.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ]
        );

        $postLink = '/de/home';
        $args = [
            'id' => 3,
            'tx_blogexample_blogpostediting' => [
                'action' => 'create',
                'controller' => 'BlogPostEditing',
            ],
        ];
        $args = $this->enrichArgumentsWithChash($args);
        $postPayload = [
            'tx_blogexample_blogpostediting' => [
                'blog' => [
                    'title' => 'A new blog entry',
                    'categories' => [
                        0 => 7,
                    ],
                ],
                '__referrer' => [
                    '@extension' => 'BlogExample',
                    '@controller' => 'BlogPostEditing',
                    '@action' => 'new',
                    'arguments' => 'YToyOntzOjY6ImFjdGlvbiI7czozOiJuZXciO3M6MTA6ImNvbnRyb2xsZXIiO3M6MTU6IkJsb2dQb3N0RWRpdGluZyI7fQ==fa17ac3725a7ae6f84fa9df1367bf78e7d937563',
                    '@request' => '{"@extension":"BlogExample","@controller":"BlogPostEditing","@action":"new"}f6dda277fa3350125290608ec08cdef1a8695f93',
                ],
                '__trustedProperties' => '{"blog":{"title":1,"categories":[1,1,1,1]},"submit":1}8915f6b454fda6161f6b61dbec880ed18c369ab4',
            ],
        ];
        $requestContext = new InternalRequestContext();

        $request = (new InternalRequest('https://www.acme.com' . $postLink))
            ->withMethod('POST')
            ->withQueryParams($args)
            ->withParsedBody($postPayload)
            ->withBody($this->createBodyFromArray($postPayload))
            ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded');

        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        $blogRepository = $this->get(BlogRepository::class);
        $blog = $blogRepository->findByUid(5);
        self::assertSame('A new blog entry', $blog->getTitle());
        $categoryUids = [];
        foreach ($blog->getCategories() as $category) {
            $categoryUids[] = $category->getUid();
        }
        self::assertSame([7], $categoryUids);
    }

    #[Test]
    public function addingANewBlogRecordWithValidationFailuresIsPrevented(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixture/BlogPostEditingData.csv');
        $this->setUpFrontendRootPageForTestCase();
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'strict'),
            ]
        );

        $postLink = '/de/home';
        $args = [
            'id' => 3,
            'tx_blogexample_blogpostediting' => [
                'action' => 'create',
                'controller' => 'BlogPostEditing',
            ],
        ];
        $args = $this->enrichArgumentsWithChash($args);
        $postPayload = [
            'tx_blogexample_blogpostediting' => [
                'blog' => [
                    'title' => '', // This intentionally left blank!
                    'categories' => [
                        0 => 7,
                    ],
                ],
                '__referrer' => [
                    '@extension' => 'BlogExample',
                    '@controller' => 'BlogPostEditing',
                    '@action' => 'new',
                    'arguments' => 'YToyOntzOjY6ImFjdGlvbiI7czozOiJuZXciO3M6MTA6ImNvbnRyb2xsZXIiO3M6MTU6IkJsb2dQb3N0RWRpdGluZyI7fQ==fa17ac3725a7ae6f84fa9df1367bf78e7d937563',
                    '@request' => '{"@extension":"BlogExample","@controller":"BlogPostEditing","@action":"new"}f6dda277fa3350125290608ec08cdef1a8695f93',
                ],
                '__trustedProperties' => '{"blog":{"title":1,"categories":[1,1,1,1]},"submit":1}8915f6b454fda6161f6b61dbec880ed18c369ab4',
            ],
        ];
        $requestContext = new InternalRequestContext();

        $request = (new InternalRequest('https://www.acme.com' . $postLink))
            ->withMethod('POST')
            ->withQueryParams($args)
            ->withParsedBody($postPayload)
            ->withBody($this->createBodyFromArray($postPayload))
            ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded');

        $response = $this->executeFrontendSubRequest($request, $requestContext);
        self::assertSame(200, $response->getStatusCode());

        // Evaluate new view, expect validation failures
        $content = (string)$response->getBody();

        self::assertStringContainsString('[ERROR] An error occurred while trying to call TYPO3Tests\BlogExample\Controller\BlogPostEditingController->createAction()', $content);
        self::assertStringContainsString('blog.title: The given subject was empty', $content);

        $blogRepository = $this->get(BlogRepository::class);
        $blog = $blogRepository->findByUid(5);
        self::assertNull($blog);
    }

    private function enrichArgumentsWithChash($arguments): array
    {
        $arguments['cHash'] = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters(HttpUtility::buildQueryString($arguments));
        return $arguments;
    }
}
