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

namespace TYPO3\CMS\Core\Tests\Unit\Page;

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AssetCollectorTest extends UnitTestCase
{
    /**
     * @var AssetCollector
     */
    protected $assetCollector;

    public function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
        $this->assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
    }

    /**
     * @param array $files
     * @param array $expectedResult
     * @dataProvider \TYPO3\CMS\Core\Tests\Unit\Page\AssetDataProvider::filesDataProvider
     */
    public function testStyleSheets(array $files, array $expectedResult): void
    {
        foreach ($files as $file) {
            [$identifier, $source, $attributes, $options] = $file;
            $this->assetCollector->addStyleSheet($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedResult, $this->assetCollector->getStyleSheets());
        self::assertSame([], $this->assetCollector->getInlineStyleSheets());
        self::assertSame([], $this->assetCollector->getInlineJavaScripts());
        self::assertSame([], $this->assetCollector->getJavaScripts());
        self::assertSame([], $this->assetCollector->getMedia());
        foreach ($files as $file) {
            [$identifier] = $file;
            $this->assetCollector->removeStyleSheet($identifier);
        }
        self::assertSame([], $this->assetCollector->getStyleSheets());
        self::assertSame([], $this->assetCollector->getInlineStyleSheets());
        self::assertSame([], $this->assetCollector->getInlineJavaScripts());
        self::assertSame([], $this->assetCollector->getJavaScripts());
        self::assertSame([], $this->assetCollector->getMedia());
    }

    /**
     * @param array $files
     * @param array $expectedResult
     * @dataProvider \TYPO3\CMS\Core\Tests\Unit\Page\AssetDataProvider::filesDataProvider
     */
    public function testJavaScript(array $files, array $expectedResult): void
    {
        foreach ($files as $file) {
            [$identifier, $source, $attributes, $options] = $file;
            $this->assetCollector->addJavaScript($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedResult, $this->assetCollector->getJavaScripts());
        self::assertSame([], $this->assetCollector->getInlineStyleSheets());
        self::assertSame([], $this->assetCollector->getInlineJavaScripts());
        self::assertSame([], $this->assetCollector->getStyleSheets());
        self::assertSame([], $this->assetCollector->getMedia());
        foreach ($files as $file) {
            [$identifier] = $file;
            $this->assetCollector->removeJavaScript($identifier);
        }
        self::assertSame([], $this->assetCollector->getJavaScripts());
        self::assertSame([], $this->assetCollector->getInlineStyleSheets());
        self::assertSame([], $this->assetCollector->getInlineJavaScripts());
        self::assertSame([], $this->assetCollector->getStyleSheets());
        self::assertSame([], $this->assetCollector->getMedia());
    }

    /**
     * @param array $sources
     * @param array $expectedResult
     * @dataProvider \TYPO3\CMS\Core\Tests\Unit\Page\AssetDataProvider::inlineDataProvider
     */
    public function testInlineJavaScript(array $sources, array $expectedResult): void
    {
        foreach ($sources as $source) {
            [$identifier, $source, $attributes, $options] = $source;
            $this->assetCollector->addInlineJavaScript($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedResult, $this->assetCollector->getInlineJavaScripts());
        self::assertSame([], $this->assetCollector->getInlineStyleSheets());
        self::assertSame([], $this->assetCollector->getJavaScripts());
        self::assertSame([], $this->assetCollector->getStyleSheets());
        self::assertSame([], $this->assetCollector->getMedia());
        foreach ($sources as $source) {
            [$identifier] = $source;
            $this->assetCollector->removeInlineJavaScript($identifier);
        }
        self::assertSame([], $this->assetCollector->getInlineJavaScripts());
        self::assertSame([], $this->assetCollector->getInlineStyleSheets());
        self::assertSame([], $this->assetCollector->getJavaScripts());
        self::assertSame([], $this->assetCollector->getStyleSheets());
        self::assertSame([], $this->assetCollector->getMedia());
    }

    /**
     * @param array $sources
     * @param array $expectedResult
     * @dataProvider \TYPO3\CMS\Core\Tests\Unit\Page\AssetDataProvider::inlineDataProvider
     */
    public function testInlineStyles(array $sources, array $expectedResult): void
    {
        foreach ($sources as $source) {
            [$identifier, $source, $attributes, $options] = $source;
            $this->assetCollector->addInlineStyleSheet($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedResult, $this->assetCollector->getInlineStyleSheets());
        self::assertSame([], $this->assetCollector->getInlineJavaScripts());
        self::assertSame([], $this->assetCollector->getJavaScripts());
        self::assertSame([], $this->assetCollector->getStyleSheets());
        self::assertSame([], $this->assetCollector->getMedia());
        foreach ($sources as $source) {
            [$identifier] = $source;
            $this->assetCollector->removeInlineStyleSheet($identifier);
        }
        self::assertSame([], $this->assetCollector->getInlineStyleSheets());
        self::assertSame([], $this->assetCollector->getInlineJavaScripts());
        self::assertSame([], $this->assetCollector->getJavaScripts());
        self::assertSame([], $this->assetCollector->getStyleSheets());
        self::assertSame([], $this->assetCollector->getMedia());
    }

    /**
     * @param array $images
     * @param array $expectedResult
     * @dataProvider \TYPO3\CMS\Core\Tests\Unit\Page\AssetDataProvider::mediaDataProvider
     */
    public function testMedia(array $images, array $expectedResult): void
    {
        foreach ($images as $image) {
            [$fileName, $additionalInformation] = $image;
            $this->assetCollector->addMedia($fileName, $additionalInformation);
        }
        self::assertSame($expectedResult, $this->assetCollector->getMedia());
        self::assertSame([], $this->assetCollector->getInlineStyleSheets());
        self::assertSame([], $this->assetCollector->getInlineJavaScripts());
        self::assertSame([], $this->assetCollector->getJavaScripts());
        self::assertSame([], $this->assetCollector->getStyleSheets());
        foreach ($images as $image) {
            [$fileName] = $image;
            $this->assetCollector->removeMedia($fileName);
        }
        self::assertSame([], $this->assetCollector->getMedia());
        self::assertSame([], $this->assetCollector->getInlineStyleSheets());
        self::assertSame([], $this->assetCollector->getInlineJavaScripts());
        self::assertSame([], $this->assetCollector->getJavaScripts());
        self::assertSame([], $this->assetCollector->getStyleSheets());
    }
}
