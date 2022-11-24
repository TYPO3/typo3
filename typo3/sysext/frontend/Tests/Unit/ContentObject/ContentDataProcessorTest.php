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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\DataProcessorRegistry;
use TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Fixtures\DataProcessorFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ContentDataProcessorTest extends UnitTestCase
{
    protected ContentDataProcessor $contentDataProcessor;
    protected Container $container;
    protected MockObject&DataProcessorRegistry $dataProcessorRegistryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
        $this->dataProcessorRegistryMock = $this->getMockBuilder(DataProcessorRegistry::class)->disableOriginalConstructor()->getMock();
        $this->dataProcessorRegistryMock->method('getDataProcessor')->willReturn(null);
        $this->contentDataProcessor = new ContentDataProcessor(
            $this->container,
            $this->dataProcessorRegistryMock
        );
    }

    /**
     * @test
     */
    public function throwsExceptionIfProcessorDoesNotExist(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1427455378);
        $contentObjectRendererStub = new ContentObjectRenderer();
        $config = [
            'dataProcessing.' => [
                '10' => 'fooClass',
            ],
        ];
        $variables = [];
        $this->contentDataProcessor->process($contentObjectRendererStub, $config, $variables);
    }

    /**
     * @test
     */
    public function throwsExceptionIfProcessorClassDoesNotImplementInterface(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1427455377);
        $contentObjectRendererStub = new ContentObjectRenderer();
        $config = [
            'dataProcessing.' => [
                '10' => static::class,
            ],
        ];
        $variables = [];
        $this->contentDataProcessor->process($contentObjectRendererStub, $config, $variables);
    }

    /**
     * @test
     */
    public function processorClassIsCalled(): void
    {
        $contentObjectRendererStub = new ContentObjectRenderer();
        $config = [
            'dataProcessing.' => [
                '10' => DataProcessorFixture::class,
                '10.' => ['foo' => 'bar'],
            ],
        ];
        $variables = [];
        self::assertSame(
            ['foo' => 'bar'],
            $this->contentDataProcessor->process($contentObjectRendererStub, $config, $variables)
        );
    }

    /**
     * @test
     */
    public function throwsExceptionIfProcessorServiceDoesNotImplementInterface(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1635927108);
        $contentObjectRendererStub = new ContentObjectRenderer();
        $this->container->set(static::class, $this);
        $config = [
            'dataProcessing.' => [
                '10' => static::class,
            ],
        ];
        $variables = [];
        $this->contentDataProcessor->process($contentObjectRendererStub, $config, $variables);
    }

    /**
     * @test
     */
    public function processorServiceIsCalled(): void
    {
        $contentObjectRendererStub = new ContentObjectRenderer();
        $this->container->set('dataProcessorFixture', new DataProcessorFixture());
        $config = [
            'dataProcessing.' => [
                '10' => 'dataProcessorFixture',
                '10.' => ['foo' => 'bar'],
            ],
        ];
        $variables = [];
        self::assertSame(
            ['foo' => 'bar'],
            $this->contentDataProcessor->process($contentObjectRendererStub, $config, $variables)
        );
    }
}
