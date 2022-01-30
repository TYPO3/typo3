<?php

declare(strict_types=1);

namespace TYPO3\CMS\Frontend\Tests\Unit\DataProcessing;

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

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase
 */
class FlexFormProcessorTest extends UnitTestCase
{
    use ProphecyTrait;

    protected ObjectProphecy $contentObjectRenderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSingletonInstances = true;
        $this->contentObjectRenderer = $this->prophesize(ContentObjectRenderer::class);
        $this->prepareFlexFormService();
    }

    /**
     * @test
     */
    public function customFieldNameDoesNotExistsWillReturnUnchangedProcessedData(): void
    {
        $processorConfiguration = ['as' => 'myOutputVariable', 'fieldName' => 'non_existing_field'];
        $this->contentObjectRenderer
            ->stdWrapValue('fieldName', $processorConfiguration, 'pi_flexform')
            ->willReturn('non_existing_field');
        $this->contentObjectRenderer
            ->stdWrapValue('as', $processorConfiguration, 'flexFormData')
            ->willReturn('myOutputVariable');

        $processedData = [
            'data' => [
                'pi_flexform' => $this->getFlexFormStructure(),
            ],
        ];

        $subject = new FlexFormProcessor();
        $expected = $subject->process(
            $this->contentObjectRenderer->reveal(),
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertSame($expected, $processedData);
    }

    /**
     * @test
     */
    public function customFieldNameDoesNotContainFlexFormDataWillReturnUnchangedProcessedData(): void
    {
        $processorConfiguration = ['as' => 'myOutputVariable', 'fieldName' => 'custom_field'];
        $this->contentObjectRenderer
            ->stdWrapValue('fieldName', $processorConfiguration, 'pi_flexform')
            ->willReturn('custom_field');
        $this->contentObjectRenderer
            ->stdWrapValue('as', $processorConfiguration, 'flexFormData')
            ->willReturn('myOutputVariable');

        $processedData = [
            'data' => [
                'custom_field' => 123456789,
            ],
        ];

        $subject = new FlexFormProcessor();
        $expected = $subject->process(
            $this->contentObjectRenderer->reveal(),
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertSame($expected, $processedData);
    }

    /**
     * @test
     */
    public function customOutputVariableForProcessorWillReturnParsedFlexFormToDataCustomVariable(): void
    {
        $processorConfiguration = ['as' => 'myCustomVar'];
        $this->contentObjectRenderer
            ->stdWrapValue('fieldName', $processorConfiguration, 'pi_flexform')
            ->willReturn('pi_flexform');
        $this->contentObjectRenderer
            ->stdWrapValue('as', $processorConfiguration, 'flexFormData')
            ->willReturn('myCustomVar');

        $processedData = [
            'data' => [
                'pi_flexform' => $this->getFlexFormStructure(),
            ],
        ];

        $subject = new FlexFormProcessor();
        $expected = $subject->process(
            $this->contentObjectRenderer->reveal(),
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertIsArray($expected['myCustomVar']);
    }

    /**
     * @test
     */
    public function defaultOutputVariableForProcessorWillBeUsed(): void
    {
        $processorConfiguration = [];
        $this->contentObjectRenderer
            ->stdWrapValue('fieldName', $processorConfiguration, 'pi_flexform')
            ->willReturn('pi_flexform');
        $this->contentObjectRenderer
            ->stdWrapValue('as', $processorConfiguration, 'flexFormData')
            ->willReturn('flexFormData');

        $processedData = [
            'data' => [
                'pi_flexform' => $this->getFlexFormStructure(),
            ],
        ];

        $subject = new FlexFormProcessor();
        $expected = $subject->process(
            $this->contentObjectRenderer->reveal(),
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertSame($expected['data']['pi_flexform'], $processedData['data']['pi_flexform']);
        self::assertIsArray($expected['flexFormData']);
    }

    /**
     * @test
     */
    public function defaultConfigurationWithCustomFieldNameWillReturnParsedFlexFormToDefaultOutputVariable(): void
    {
        $processorConfiguration = ['as' => 'myOutputVariable', 'fieldName' => 'my_flexform'];
        $this->contentObjectRenderer
            ->stdWrapValue('fieldName', $processorConfiguration, 'pi_flexform')
            ->willReturn('my_flexform');
        $this->contentObjectRenderer
            ->stdWrapValue('as', $processorConfiguration, 'flexFormData')
            ->willReturn('myOutputVariable');

        $processedData = [
            'data' => [
                'my_flexform' => $this->getFlexFormStructure(),
            ],
        ];

        $subject = new FlexFormProcessor();
        $expected = $subject->process(
            $this->contentObjectRenderer->reveal(),
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertIsArray($expected['myOutputVariable']);
    }

    /**
     * @test
     */
    public function subDataProcessorIsResolved(): void
    {
        $this->prepareFlexFormServiceWithSubDataProcessorData();

        $processorConfiguration['dataProcessing.'] = [10 => 'Vendor\Acme\DataProcessing\FooProcessor'];

        $processedData = [
            'data' => [
                'pi_flexform' => $this->getFlexFormStructure(),
            ],
        ];

        $this->contentObjectRenderer
            ->stdWrapValue('fieldName', $processorConfiguration, 'pi_flexform')
            ->willReturn('pi_flexform');
        $this->contentObjectRenderer
            ->stdWrapValue('as', $processorConfiguration, 'flexFormData')
            ->willReturn('flexFormData');
        $convertedFlexFormData = [
            'options' => [
                'hotels' => 0,
                'images' => '12',
            ],
        ];
        $this->contentObjectRenderer->start([$convertedFlexFormData])->shouldBeCalled();

        $contentDataProcessor = $this->prophesize(ContentDataProcessor::class);
        $renderedDataFromProcessors = [
            'options' => [
                'hotels' => 0,
                'images' => 'img/foo.jpg',
            ],
        ];
        $contentDataProcessor
            ->process($this->contentObjectRenderer->reveal(), $processorConfiguration, $convertedFlexFormData)
            ->willReturn($renderedDataFromProcessors);

        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->contentObjectRenderer->reveal());
        GeneralUtility::addInstance(ContentDataProcessor::class, $contentDataProcessor->reveal());

        $subject = new FlexFormProcessor();
        $actual = $subject->process(
            $this->contentObjectRenderer->reveal(),
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertSame(array_merge($processedData, ['flexFormData' => $renderedDataFromProcessors]), $actual);
    }

    private function getFlexFormStructure(): string
    {
        return '<![CDATA[<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
            . '<T3FlexForms>
                        <data>
                            <sheet index="options">
                                <language index="lDEF">
                                    <field index="hotels">
                                        <value index="vDEF">0</value>
                                    </field>
                                </language>
                            </sheet>
                        </data>
                    </T3FlexForms>'
            . ']]>';
    }

    private function prepareFlexFormService(): void
    {
        $convertedFlexFormData = [
            'options' => [
                'hotels' => 0,
            ],
        ];

        $flexFormService = $this->prophesize(FlexFormService::class);
        $flexFormService->convertFlexFormContentToArray($this->getFlexFormStructure())->willReturn($convertedFlexFormData);
        GeneralUtility::setSingletonInstance(FlexFormService::class, $flexFormService->reveal());
    }

    private function prepareFlexFormServiceWithSubDataProcessorData(): void
    {
        $convertedFlexFormData = [
            'options' => [
                'hotels' => 0,
                'images' => '12',
            ],
        ];

        $flexFormService = $this->prophesize(FlexFormService::class);
        $flexFormService->convertFlexFormContentToArray($this->getFlexFormStructure())->willReturn($convertedFlexFormData);
        GeneralUtility::setSingletonInstance(FlexFormService::class, $flexFormService->reveal());
    }
}
