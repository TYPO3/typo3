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

namespace TYPO3\CMS\Backend\Tests\Functional\Preview;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Preview\StandardPreviewRendererResolver;
use TYPO3\CMS\Backend\Tests\Functional\Preview\Fixtures\CustomPreviewRendererFixture;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class StandardPreviewRendererResolverTest extends FunctionalTestCase
{
    private StandardPreviewRendererResolver $subject;
    private TcaSchemaFactory $tcaSchemaFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $this->subject = $this->get(StandardPreviewRendererResolver::class);
    }

    #[Test]
    public function resolveStandardContentPreviewRenderer(): void
    {
        $record = new RawRecord(1, 1, [
            'CType' => 'text',
        ], new ComputedProperties(), 'tt_content.text');

        $result = $this->subject->resolveRendererFor($record);

        self::assertInstanceOf(StandardContentPreviewRenderer::class, $result);
    }

    #[Test]
    public function resolveCustomContentPreviewRenderer(): void
    {
        $GLOBALS['TCA']['tt_content']['types']['my_plugin_pi1'] = [
            'showitem' => 'CType',
            'previewRenderer' => CustomPreviewRendererFixture::class,
        ];
        $this->tcaSchemaFactory->rebuild($GLOBALS['TCA']);

        $record = new RawRecord(1, 1, [
            'CType' => 'my_plugin_pi1',
        ], new ComputedProperties(), 'tt_content.my_plugin_pi1');

        $result = $this->subject->resolveRendererFor($record);

        self::assertInstanceOf(CustomPreviewRendererFixture::class, $result);
    }

    #[Test]
    public function doesNotResolveCustomContentPreviewRendererAsArray(): void
    {
        $GLOBALS['TCA']['tt_content']['types']['my_plugin_pi1'] = [
            'showitem' => 'CType',
            'previewRenderer' => [
                'custom' => CustomPreviewRendererFixture::class,
            ],
        ];
        $this->tcaSchemaFactory->rebuild($GLOBALS['TCA']);

        $record = new RawRecord(1, 1, [
            'CType' => 'my_plugin_pi1',
        ], new ComputedProperties(), 'tt_content.my_plugin_pi1');

        $result = $this->subject->resolveRendererFor($record);

        self::assertInstanceOf(StandardContentPreviewRenderer::class, $result);
    }

    #[Test]
    public function throwsExceptionWithNoPreviewRendererDefined(): void
    {
        $GLOBALS['TCA']['tx_test'] = [
            'ctrl' => [
                'label' => 'title',
            ],
            'columns' => [
                'title' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        $this->tcaSchemaFactory->rebuild($GLOBALS['TCA']);

        $record = new RawRecord(1, 1, [
            'title' => 'Test',
        ], new ComputedProperties(), 'tx_test');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1477520356);

        $this->subject->resolveRendererFor($record);
    }
}
