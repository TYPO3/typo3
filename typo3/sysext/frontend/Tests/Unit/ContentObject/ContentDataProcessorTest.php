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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Fixtures\DataProcessorFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor
 */
class ContentDataProcessorTest extends UnitTestCase
{
    use ProphecyTrait;

    protected ContentDataProcessor $contentDataProcessor;

    /** @var ObjectProphecy<ContainerInterface> */
    protected ObjectProphecy $containerProphecy;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->containerProphecy = $this->prophesize(ContainerInterface::class);
        $this->containerProphecy->has(Argument::any())->willReturn(false);
        $this->contentDataProcessor = new ContentDataProcessor(
            $this->containerProphecy->reveal()
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
        $this->containerProphecy->has(static::class)->willReturn(true);
        $this->containerProphecy->get(static::class)->willReturn($this);
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
        $this->containerProphecy->has('dataProcessorFixture')->willReturn(true);
        $this->containerProphecy->get('dataProcessorFixture')->willReturn(new DataProcessorFixture());
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
