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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\AssetRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AssetRendererTest extends UnitTestCase
{
    /**
     * @var AssetRenderer
     */
    protected $assetRenderer;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->assetRenderer = GeneralUtility::makeInstance(
            AssetRenderer::class,
            null,
            $this->eventDispatcher
        );
    }

    /**
     * @param array $files
     * @param array $expectedResult
     * @param array $expectedMarkup
     * @dataProvider \TYPO3\CMS\Core\Tests\Unit\Page\AssetDataProvider::filesDataProvider
     */
    public function testStyleSheets(array $files, array $expectedResult, array $expectedMarkup): void
    {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        foreach ($files as $file) {
            [$identifier, $source, $attributes, $options] = $file;
            $assetCollector->addStyleSheet($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedMarkup['css_no_prio'], $this->assetRenderer->renderStyleSheets());
        self::assertSame($expectedMarkup['css_prio'], $this->assetRenderer->renderStyleSheets(true));
    }

    /**
     * @param array $files
     * @param array $expectedResult
     * @param array $expectedMarkup
     * @dataProvider \TYPO3\CMS\Core\Tests\Unit\Page\AssetDataProvider::filesDataProvider
     */
    public function testJavaScript(array $files, array $expectedResult, array $expectedMarkup): void
    {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        foreach ($files as $file) {
            [$identifier, $source, $attributes, $options] = $file;
            $assetCollector->addJavaScript($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedMarkup['js_no_prio'], $this->assetRenderer->renderJavaScript());
        self::assertSame($expectedMarkup['js_prio'], $this->assetRenderer->renderJavaScript(true));
    }

    /**
     * @param array $sources
     * @param array $expectedResult
     * @param array $expectedMarkup
     * @dataProvider \TYPO3\CMS\Core\Tests\Unit\Page\AssetDataProvider::inlineDataProvider
     */
    public function testInlineJavaScript(array $sources, array $expectedResult, array $expectedMarkup): void
    {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        foreach ($sources as $source) {
            [$identifier, $source, $attributes, $options] = $source;
            $assetCollector->addInlineJavaScript($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedMarkup['js_no_prio'], $this->assetRenderer->renderInlineJavaScript());
        self::assertSame($expectedMarkup['js_prio'], $this->assetRenderer->renderInlineJavaScript(true));
    }

    /**
     * @param array $sources
     * @param array $expectedResult
     * @param array $expectedMarkup
     * @dataProvider \TYPO3\CMS\Core\Tests\Unit\Page\AssetDataProvider::inlineDataProvider
     */
    public function testInlineStyleSheets(array $sources, array $expectedResult, array $expectedMarkup): void
    {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        foreach ($sources as $source) {
            [$identifier, $source, $attributes, $options] = $source;
            $assetCollector->addInlineStyleSheet($identifier, $source, $attributes, $options);
        }
        self::assertSame($expectedMarkup['css_no_prio'], $this->assetRenderer->renderInlineStyleSheets());
        self::assertSame($expectedMarkup['css_prio'], $this->assetRenderer->renderInlineStyleSheets(true));
    }

    /**
     * @param string $renderMethodName
     * @param bool $isInline
     * @param bool $priority
     * @param string $eventClassName
     * @dataProvider \TYPO3\CMS\Core\Tests\Unit\Page\AssetDataProvider::renderMethodsAndEventsDataProvider
     */
    public function testBeforeRenderingEvent(
        string $renderMethodName,
        bool $isInline,
        bool $priority,
        string $eventClassName
    ): void {
        $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
        $event = new $eventClassName(
            $assetCollector,
            $isInline,
            $priority
        );

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with($event);

        $this->assetRenderer->$renderMethodName($priority);
    }
}
