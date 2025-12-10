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

namespace TYPO3\CMS\Backend\Tests\Functional\View\Event;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Preview\StandardPreviewRendererResolver;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\Event\AfterPageContentPreviewRenderedEvent;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Testing event AfterPageContentPreviewRenderedEvent
 */
final class AfterPageContentPreviewRenderedTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function eventMethodReturnsExpectedValue(): void
    {
        $standardPreviewRenderer = $this->createMock(StandardContentPreviewRenderer::class);
        $standardPreviewRenderer->expects($this->once())->method('renderPageModulePreviewHeader')->willReturn('My Preview Header');
        $standardPreviewRenderer->expects($this->once())->method('renderPageModulePreviewContent')->willReturn('My Content');
        $standardPreviewRenderer->expects($this->once())->method('wrapPageModulePreview')->willReturn('<div class="element-preview"><div class="element-preview-header">My Preview Header</div><div class="element-preview-content">My Content</div></div>');

        $standardPreviewResolver = $this->createMock(StandardPreviewRendererResolver::class);
        $standardPreviewResolver->expects($this->once())->method('resolveRendererFor')->willReturn($standardPreviewRenderer);
        GeneralUtility::addInstance(StandardPreviewRendererResolver::class, $standardPreviewResolver);

        $mockedContext = $this->createMock(PageLayoutContext::class);
        $mockedColumn = $this->createMock(GridColumn::class);

        $rawRecord = new RawRecord(1, 1, [
            'CType' => 'text',
        ], new ComputedProperties(), 'tt_content.text');
        $mockedRecord = new Record($rawRecord, [], null);

        $mockedGridColumnItem = $this->getMockBuilder(GridColumnItem::class)
            ->setConstructorArgs([$mockedContext, $mockedColumn, $mockedRecord, 'tt_content'])
            ->onlyMethods(['getRecordType'])
            ->getMock();

        $mockedGridColumnItem->method('getRecordType')->willReturn('myRecordType');

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-pagecontent-preview-rendered-listener',
            static function (AfterPageContentPreviewRenderedEvent $event) {
                $event->setPreviewContent('_before_' . $event->getPreviewContent() . '_after_');
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterPageContentPreviewRenderedEvent::class, 'after-pagecontent-preview-rendered-listener');

        $preview = $mockedGridColumnItem->getPreview();
        self::assertEquals('_before_<div class="element-preview"><div class="element-preview-header">My Preview Header</div><div class="element-preview-content">My Content</div></div>_after_', $preview);
    }

}
