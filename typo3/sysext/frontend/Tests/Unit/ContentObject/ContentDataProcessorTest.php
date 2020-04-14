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

use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Fixtures\DataProcessorFixture;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor
 */
class ContentDataProcessorTest extends UnitTestCase
{
    /**
     * @var ContentDataProcessor
     */
    protected $contentDataProcessor;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->contentDataProcessor = new ContentDataProcessor();
    }

    /**
     * @test
     */
    public function throwsExceptionIfProcessorClassDoesNotExist(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1427455378);
        $contentObjectRendererStub = new ContentObjectRenderer();
        $config = [
            'dataProcessing.' => [
                '10' => 'fooClass'
            ]
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
                '10' => static::class
            ]
        ];
        $variables = [];
        $this->contentDataProcessor->process($contentObjectRendererStub, $config, $variables);
    }

    /**
     * @test
     */
    public function processorIsCalled(): void
    {
        $contentObjectRendererStub = new ContentObjectRenderer();
        $config = [
            'dataProcessing.' => [
                '10' => DataProcessorFixture::class,
                '10.' => ['foo' => 'bar'],
            ]
        ];
        $variables = [];
        self::assertSame(
            ['foo' => 'bar'],
            $this->contentDataProcessor->process($contentObjectRendererStub, $config, $variables)
        );
    }
}
