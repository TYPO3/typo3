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
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StandardPreviewRendererResolverTest extends UnitTestCase
{
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

        $tcaSchemaFactory = $this->createTcaSchemaFactory();
        $tcaSchemaFactory->rebuild($this->getDefaultTca());
        $subject = new StandardPreviewRendererResolver($tcaSchemaFactory);

        self::assertEquals(
            StandardContentPreviewRenderer::class,
            get_class($subject->resolveRendererFor($table, $row, 0))
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
        $tcaSchemaFactory = $this->createTcaSchemaFactory();
        $tca = $this->getDefaultTca();
        $tca[$table]['types']['my_plugin_pi1']['previewRenderer'] = get_class($customPreviewRenderer);
        $tcaSchemaFactory->rebuild($tca);
        $subject = new StandardPreviewRendererResolver($tcaSchemaFactory);

        self::assertEquals(
            get_class($customPreviewRenderer),
            get_class($subject->resolveRendererFor($table, $row, 0))
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

        $tcaSchemaFactory = $this->createTcaSchemaFactory();
        $tca = $this->getDefaultTca();
        $tca[$table]['types']['my_plugin_pi1']['previewRenderer']['custom'] = get_class($customPreviewRenderer);
        $tcaSchemaFactory->rebuild($tca);
        $subject = new StandardPreviewRendererResolver($tcaSchemaFactory);

        self::assertEquals(
            StandardContentPreviewRenderer::class,
            get_class($subject->resolveRendererFor($table, $row, 0))
        );
    }

    #[Test]
    public function getExceptionWithNoPreviewRendererDefined(): void
    {
        $tcaSchemaFactory = $this->createTcaSchemaFactory();
        $tca = $this->getDefaultTca();
        $tca['pages'] = [
            'ctrl' => [
                'label' => 'nothing',
            ],
            'columns' => [
                'nothing' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];
        $tcaSchemaFactory->rebuild($tca);
        $subject = new StandardPreviewRendererResolver($tcaSchemaFactory);

        $table = 'pages';
        $row = [
            'CType' => PageRepository::DOKTYPE_DEFAULT,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1477520356);

        $subject->resolveRendererFor($table, $row, 0);
    }

    private function createTcaSchemaFactory(): TcaSchemaFactory
    {
        return new TcaSchemaFactory(
            new RelationMapBuilder(
                $this->createMock(FlexFormTools::class)
            ),
            new FieldTypeFactory(),
            'cacheIdentifier',
            new NullFrontend('runtime')
        );
    }

    private function getDefaultTca(): array
    {
        $tca['tt_content'] = [
            'ctrl' => [
                'type' => 'CType',
                'previewRenderer' => StandardContentPreviewRenderer::class,
            ],
            'columns' => [
                'CType' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['label' => 'Text', 'value' => 'text'],
                            ['label' => 'Image', 'value' => 'image'],
                            ['label' => 'My Plugin', 'value' => 'my_plugin_pi1'],
                        ],
                    ],
                ],
            ],
            'types' => [
                'text' => ['showitem' => 'CType'],
                'image' => ['showitem' => 'CType'],
                'my_plugin_pi1' => ['showitem' => 'CType'],
            ],
        ];
        return $tca;
    }
}
