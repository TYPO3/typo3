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

namespace TYPO3\CMS\IndexedSearch\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\IndexedSearch\Indexer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class IndexerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        $request = (new ServerRequest('https://foo.de', 'GET'));
        $normalizedParams = NormalizedParams::createFromRequest($request);
        $request = $request->withAttribute('normalizedParams', $normalizedParams);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    #[Test]
    public function extractHyperLinksDoesNotReturnNonExistingLocalPath(): void
    {
        $html = 'test <a href="' . StringUtility::getUniqueId() . '">test</a> test';
        $subject = $this->getMockBuilder(Indexer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $result = $subject->extractHyperLinks($html);
        self::assertCount(1, $result);
        self::assertEquals('', $result[0]['localPath']);
    }

    #[Test]
    public function extractHyperLinksReturnsCorrectPathWithBaseUrl(): void
    {
        $baseURL = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getSiteUrl();
        $html = 'test <a href="' . $baseURL . 'index.php">test</a> test';
        $subject = $this->getMockBuilder(Indexer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $result = $subject->extractHyperLinks($html);
        self::assertCount(1, $result);
        self::assertEquals(Environment::getPublicPath() . '/index.php', $result[0]['localPath']);
    }

    #[Test]
    public function extractHyperLinksFindsCorrectPathWithAbsolutePath(): void
    {
        $html = 'test <a href="index.php">test</a> test';
        $subject = $this->getMockBuilder(Indexer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $result = $subject->extractHyperLinks($html);
        self::assertCount(1, $result);
        self::assertEquals(Environment::getPublicPath() . '/index.php', $result[0]['localPath']);
    }

    #[Test]
    public function extractHyperLinksFindsCorrectPathUsingAbsRefPrefix(): void
    {
        $absRefPrefix = '/' . StringUtility::getUniqueId();
        $html = 'test <a href="' . $absRefPrefix . 'index.php">test</a> test';
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([
            'absRefPrefix' => $absRefPrefix,
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $subject = $this->getMockBuilder(Indexer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $result = $subject->extractHyperLinks($html);
        self::assertCount(1, $result);
        self::assertEquals(Environment::getPublicPath() . '/index.php', $result[0]['localPath']);
    }

    #[Test]
    public function extractBaseHrefExtractsBaseHref(): void
    {
        $baseHref = 'http://example.com/';
        $html = '<html><head><Base Href="' . $baseHref . '" /></head></html>';
        $subject = $this->getMockBuilder(Indexer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $result = $subject->extractBaseHref($html);
        self::assertEquals($baseHref, $result);
    }

    /**
     * Tests whether indexer can extract content between "TYPO3SEARCH_begin" and "TYPO3SEARCH_end" markers
     */
    #[Test]
    public function typoSearchTagsRemovesBodyContentOutsideMarkers(): void
    {
        $body = <<<EOT
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<title>Some Title</title>
<link href='css/normalize.css' rel='stylesheet'/>
</head>
<body>
<div>
<div class="non_searchable">
    not searchable content
</div>
<!--TYPO3SEARCH_begin-->
<div class="searchable">
    lorem ipsum
</div>
<!--TYPO3SEARCH_end-->
<div class="non_searchable">
    not searchable content
</div>
</body>
</html>
EOT;
        $expected = <<<EOT

<div class="searchable">
    lorem ipsum
</div>

EOT;

        $subject = $this->getMockBuilder(Indexer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $result = $subject->typoSearchTags($body);
        self::assertTrue($result);
        self::assertEquals($expected, $body);
    }

    /**
     * Tests whether indexer can extract content between multiple pairs of "TYPO3SEARCH" markers
     */
    #[Test]
    public function typoSearchTagsHandlesMultipleMarkerPairs(): void
    {
        $body = <<<EOT
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
<title>Some Title</title>
<link href='css/normalize.css' rel='stylesheet'/>
</head>
<body>
<div>
<div class="non_searchable">
    not searchable content
</div>
<!--TYPO3SEARCH_begin-->
<div class="searchable">
    lorem ipsum
</div>
<!--TYPO3SEARCH_end-->
<div class="non_searchable">
    not searchable content
</div>
<!--TYPO3SEARCH_begin-->
<div class="searchable">
    lorem ipsum2
</div>
<!--TYPO3SEARCH_end-->
<div class="non_searchable">
    not searchable content
</div>
</body>
</html>
EOT;
        $expected = <<<EOT

<div class="searchable">
    lorem ipsum
</div>

<div class="searchable">
    lorem ipsum2
</div>

EOT;

        $subject = $this->getMockBuilder(Indexer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $result = $subject->typoSearchTags($body);
        self::assertTrue($result);
        self::assertEquals($expected, $body);
    }
}
