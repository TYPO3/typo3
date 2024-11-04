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

namespace TYPO3\CMS\Backend\Tests\Unit\Preview;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Preview\PreviewRendererInterface;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\Preview\StandardPreviewRendererResolver;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StandardPreviewRendererResolverTest extends UnitTestCase
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
        ];
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    #[Test]
    public function resolveStandardContentPreviewRenderer(): void
    {
        $table = 'tt_content';
        $row = [
            'CType' => 'text',
        ];

        self::assertEquals(
            StandardContentPreviewRenderer::class,
            get_class($this->subject->resolveRendererFor($table, $row, 0))
        );
    }

    #[Test]
    public function resolveCustomContentPreviewRenderer(): void
    {
        $customPreviewRenderer = $this->getMockBuilder(PreviewRendererInterface::class)->getMock();

        $table = 'tt_content';
        $row = [
            'CType' => 'my_plugin_pi1',
        ];
        $GLOBALS['TCA'][$table]['types']['my_plugin_pi1']['previewRenderer'] = get_class($customPreviewRenderer);

        self::assertEquals(
            get_class($customPreviewRenderer),
            get_class($this->subject->resolveRendererFor($table, $row, 0))
        );
    }

    #[Test]
    public function doesNotResolveCustomContentPreviewRendererAsArray(): void
    {
        $customPreviewRenderer = $this->getMockBuilder(PreviewRendererInterface::class)->getMock();

        $table = 'tt_content';
        $row = [
            'CType' => 'my_plugin_pi1',
        ];
        $GLOBALS['TCA'][$table]['types']['my_plugin_pi1']['previewRenderer']['custom'] = get_class($customPreviewRenderer);

        self::assertEquals(
            StandardContentPreviewRenderer::class,
            get_class($this->subject->resolveRendererFor($table, $row, 0))
        );
    }

    #[Test]
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
