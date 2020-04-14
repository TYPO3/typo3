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

namespace TYPO3\CMS\Seo\Tests\Functional\XmlSitemap;

/**
 * Contains functional tests for the XmlSitemap Index with
 * $GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault'] = true
 */
class XmlSitemapPagesWithHideIfNotTranslatedTest extends AbstractXmlSitemapPagesTest
{
    /**
     * This inverts the meaning of the
     * "Hide page if no translation for current language exists"
     * checkbox (l18n_cfg & 2).
     *
     * @var array
     */
    protected $configurationToUseInTestInstance = [
        'FE' => [
            'hidePagesIfNotTranslatedByDefault' => true
        ]
    ];

    /**
     * Page marked as "Hide page if no translation for current language exists".
     * With "hidePagesIfNotTranslatedByDefault" enabled we expect to see the page
     * because it does NOT exist in the requested language (DE).
     *
     * @test
     */
    public function pagesSitemapInAlternativeLanguageDoesContainSiteThatIsHiddenIfNotTranslated(): void
    {
        self::assertStringContainsString(
            '<loc>http://localhost/de/dummy-1-2-5-fr</loc>',
            (string)$this->getResponse('http://localhost/de/')->getBody()
        );
    }

    /**
     * Behavior is not changed with "hidePagesIfNotTranslatedByDefault"
     *
     * @test
     */
    public function pagesSitemapContainsTranslatedPages(): void
    {
        self::assertEquals(
            4,
            (new \SimpleXMLElement((string)$this->getResponse('http://localhost/fr/')->getBody()))->count()
        );
    }

    /**
     * Behavior is not changed with "hidePagesIfNotTranslatedByDefault"
     *
     * @test
     */
    public function pagesSitemapDoesNotContainUntranslatedPages(): void
    {
        self::assertStringNotContainsString(
            '<loc>http://localhost/dummy-1-4</loc>',
            (string)$this->getResponse('http://localhost/fr/')->getBody()
        );
    }

    /**
     * Fallback strategy for pages is butchered by
     * "hidePagesIfNotTranslatedByDefault" completely.
     *
     * @test
     */
    public function pagesSitemapDoesNotCareAboutFallbackStrategy(): void
    {
        self::assertStringNotContainsString(
            '<loc>http://localhost/de/dummy-1-3-fr</loc>',
            (string)$this->getResponse('http://localhost/de/')->getBody()
        );
    }
}
