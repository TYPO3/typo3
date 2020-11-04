<?php

declare(strict_types=1);

/*
 * This file is part of a TYPO3 extension.
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

namespace TYPO3\CMS\Backend\Tests\Unit\Preview;

use TYPO3\CMS\Backend\Preview\PreviewRendererInterface;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Preview\StandardPreviewRendererResolver;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StandardPreviewRendererResolverTest extends UnitTestCase
{
    protected StandardPreviewRendererResolver $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new StandardPreviewRendererResolver();
        $GLOBALS['TCA']['tt_content'] = [
            'ctrl' => [
                'type' => 'CType',
                'previewRenderer' => StandardContentPreviewRenderer::class,
            ],
            'types' => [
                'list' => [
                    'subtype_value_field' => 'list_type'
                ]
            ]
        ];
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function resolveStandardContentPreviewRenderer(): void
    {
        $table = 'tt_content';
        $row = [
            'CType' => 'text'
        ];

        self::assertEquals(
            StandardContentPreviewRenderer::class,
            get_class($this->subject->resolveRendererFor($table, $row, 0))
        );
    }

    /**
     * @test
     */
    public function resolveCustomContentPreviewRenderer(): void
    {
        $customPreviewRenderer = $this->getMockBuilder(PreviewRendererInterface::class)->getMock();

        $table = 'tt_content';
        $row = [
            'CType' => 'list',
            'list_type' => 'custom'
        ];
        $GLOBALS['TCA'][$table]['types']['list']['previewRenderer']['custom'] = get_class($customPreviewRenderer);

        self::assertEquals(
            get_class($customPreviewRenderer),
            get_class($this->subject->resolveRendererFor($table, $row, 0))
        );
    }

    /**
     * @test
     */
    public function resolveStandardContentPreviewRendererWithCustomPreviewRendererDefined(): void
    {
        $customPreviewRenderer = $this->getMockBuilder(PreviewRendererInterface::class)->getMock();

        $table = 'tt_content';
        $row = [
            'CType' => 'list',
            'list_type' => 'default'
        ];
        $GLOBALS['TCA'][$table]['types']['list']['previewRenderer']['custom'] = get_class($customPreviewRenderer);

        self::assertEquals(
            StandardContentPreviewRenderer::class,
            get_class($this->subject->resolveRendererFor($table, $row, 0))
        );
    }

    /**
     * @test
     */
    public function resolveStandardContentPreviewRendererWithGeneralPreviewRendererDefinedForAllSubTypes(): void
    {
        $customPreviewRenderer = $this->getMockBuilder(PreviewRendererInterface::class)->getMock();

        $table = 'tt_content';
        $row = [
            'CType' => 'list',
            'list_type' => 'default'
        ];
        $GLOBALS['TCA'][$table]['types']['list']['previewRenderer'] = get_class($customPreviewRenderer);

        self::assertEquals(
            get_class($customPreviewRenderer),
            get_class($this->subject->resolveRendererFor($table, $row, 0))
        );
    }

    /**
     * @test
     */
    public function getExceptionWithNoPreviewRendererDefined(): void
    {
        $GLOBALS['TCA']['pages']['ctrl'] = [];

        $table = 'pages';
        $row = [
            'CType' => PageRepository::DOKTYPE_DEFAULT,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1477520356);

        $this->subject->resolveRendererFor($table, $row, 0);
    }
}
