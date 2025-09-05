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

namespace TYPO3\CMS\Frontend\Tests\Unit\DataProcessing;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentDataProcessor;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor;
use TYPO3\CMS\Frontend\Resource\FileCollector;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FlexFormProcessorTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    protected MockObject&ContentObjectRenderer $contentObjectRendererMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contentObjectRendererMock = $this->getMockBuilder(ContentObjectRenderer::class)->disableOriginalConstructor()->getMock();
        $this->prepareFlexFormService();
    }

    #[Test]
    public function customFieldNameDoesNotExistsWillReturnUnchangedProcessedData(): void
    {
        $processorConfiguration = ['as' => 'myOutputVariable', 'fieldName' => 'non_existing_field'];
        $this->contentObjectRendererMock->method('stdWrapValue')->willReturnMap([
            ['fieldName', $processorConfiguration, 'pi_flexform', 'non_existing_field'],
            ['as', $processorConfiguration, 'flexFormData', 'myOutputVariable'],
        ]);

        $processedData = [
            'data' => [
                'pi_flexform' => $this->getFlexFormStructure(),
            ],
        ];

        $subject = new FlexFormProcessor();
        $expected = $subject->process(
            $this->contentObjectRendererMock,
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertSame($expected, $processedData);
    }

    #[Test]
    public function customFieldNameDoesNotContainFlexFormDataWillReturnUnchangedProcessedData(): void
    {
        $processorConfiguration = ['as' => 'myOutputVariable', 'fieldName' => 'custom_field'];
        $this->contentObjectRendererMock->method('stdWrapValue')->willReturnMap([
            ['fieldName', $processorConfiguration, 'pi_flexform', 'non_existing_field'],
            ['as', $processorConfiguration, 'flexFormData', 'myOutputVariable'],
        ]);

        $processedData = [
            'data' => [
                'custom_field' => 123456789,
            ],
        ];

        $subject = new FlexFormProcessor();
        $expected = $subject->process(
            $this->contentObjectRendererMock,
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertSame($expected, $processedData);
    }

    #[Test]
    public function customOutputVariableForProcessorWillReturnParsedFlexFormToDataCustomVariable(): void
    {
        $processorConfiguration = ['as' => 'myCustomVar'];
        $this->contentObjectRendererMock->method('stdWrapValue')->willReturnMap([
            ['fieldName', $processorConfiguration, 'pi_flexform', 'pi_flexform'],
            ['as', $processorConfiguration, 'flexFormData', 'myCustomVar'],
        ]);

        $processedData = [
            'data' => [
                'pi_flexform' => $this->getFlexFormStructure(),
            ],
        ];

        $subject = new FlexFormProcessor();
        $expected = $subject->process(
            $this->contentObjectRendererMock,
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertIsArray($expected['myCustomVar']);
    }

    #[Test]
    public function defaultOutputVariableForProcessorWillBeUsed(): void
    {
        $processorConfiguration = [];
        $this->contentObjectRendererMock->method('stdWrapValue')->willReturnMap([
            ['fieldName', $processorConfiguration, 'pi_flexform', 'pi_flexform'],
            ['as', $processorConfiguration, 'flexFormData', 'flexFormData'],
        ]);

        $processedData = [
            'data' => [
                'pi_flexform' => $this->getFlexFormStructure(),
            ],
        ];

        $subject = new FlexFormProcessor();
        $expected = $subject->process(
            $this->contentObjectRendererMock,
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertSame($expected['data']['pi_flexform'], $processedData['data']['pi_flexform']);
        self::assertIsArray($expected['flexFormData']);
    }

    #[Test]
    public function defaultConfigurationWithCustomFieldNameWillReturnParsedFlexFormToDefaultOutputVariable(): void
    {
        $processorConfiguration = ['as' => 'myOutputVariable', 'fieldName' => 'my_flexform'];
        $this->contentObjectRendererMock->method('stdWrapValue')->willReturnMap([
            ['fieldName', $processorConfiguration, 'pi_flexform', 'my_flexform'],
            ['as', $processorConfiguration, 'flexFormData', 'myOutputVariable'],
        ]);

        $processedData = [
            'data' => [
                'my_flexform' => $this->getFlexFormStructure(),
            ],
        ];

        $subject = new FlexFormProcessor();
        $expected = $subject->process(
            $this->contentObjectRendererMock,
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertIsArray($expected['myOutputVariable']);
    }

    #[Test]
    public function subDataProcessorIsResolved(): void
    {
        $this->prepareFlexFormServiceWithSubDataProcessorData();

        $processorConfiguration['dataProcessing.'] = [10 => 'Vendor\Acme\DataProcessing\FooProcessor'];

        $processedData = [
            'data' => [
                'pi_flexform' => $this->getFlexFormStructure(),
            ],
        ];

        $this->contentObjectRendererMock->method('stdWrapValue')->willReturnMap([
            ['fieldName', $processorConfiguration, 'pi_flexform', 'pi_flexform'],
            ['as', $processorConfiguration, 'flexFormData', 'flexFormData'],
        ]);
        $convertedFlexFormData = [
            'options' => [
                'hotels' => 0,
                'images' => '12',
            ],
        ];
        $this->contentObjectRendererMock->expects($this->once())->method('start')->with([$convertedFlexFormData]);

        $contentDataProcessorMock = $this->getMockBuilder(ContentDataProcessor::class)->disableOriginalConstructor()->getMock();
        $renderedDataFromProcessors = [
            'options' => [
                'hotels' => 0,
                'images' => 'img/foo.jpg',
            ],
        ];
        $contentDataProcessorMock
            ->method('process')
            ->with($this->contentObjectRendererMock, $processorConfiguration, $convertedFlexFormData)
            ->willReturn($renderedDataFromProcessors);

        GeneralUtility::addInstance(ContentObjectRenderer::class, $this->contentObjectRendererMock);
        GeneralUtility::addInstance(ContentDataProcessor::class, $contentDataProcessorMock);

        $subject = new FlexFormProcessor();
        $actual = $subject->process(
            $this->contentObjectRendererMock,
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertSame(array_merge($processedData, ['flexFormData' => $renderedDataFromProcessors]), $actual);
    }

    #[Test]
    public function falReferenceIsResolved(): void
    {
        $this->prepareFlexFormServiceWithFalReferences();

        $processorConfiguration = [];
        $processorConfiguration['references.']['options.'] = [
            'image' => 'my_flexform_image',
        ];

        $renderedDataFromProcessors = [
            'options' => [
                'hotels' => 0,
                'image' => [
                    0 => 'img/foo.jpg',
                ],
            ],
        ];

        $this->contentObjectRendererMock->method('stdWrapValue')->willReturnMap([
            ['fieldName', $processorConfiguration, 'pi_flexform', 'pi_flexform'],
            ['as', $processorConfiguration, 'flexFormData', 'flexFormData'],
        ]);
        $this->contentObjectRendererMock->method('getCurrentTable')->willReturn('tt_content');
        $fileCollectorMock = $this->getMockBuilder(FileCollector::class)->disableOriginalConstructor()->getMock();
        $fileCollectorMock
            ->expects($this->exactly(1))
            ->method('addFilesFromRelation')
            ->with('tt_content', $processorConfiguration['references.']['options.']['image'], []);
        $fileCollectorMock->method('getFiles')->willReturn($renderedDataFromProcessors['options']['image']);

        GeneralUtility::addInstance(FileCollector::class, $fileCollectorMock);

        $processedData = [
            'data' => [
                'pi_flexform' => $this->getFlexFormStructure(),
            ],
        ];
        $subject = new FlexFormProcessor();
        $actual = $subject->process(
            $this->contentObjectRendererMock,
            [],
            $processorConfiguration,
            $processedData
        );

        self::assertIsArray($actual['flexFormData']);
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

        $flexFormService = $this->getMockBuilder(FlexFormService::class)->disableOriginalConstructor()->getMock();
        $flexFormService->method('convertFlexFormContentToArray')->with($this->getFlexFormStructure())->willReturn($convertedFlexFormData);
        GeneralUtility::setSingletonInstance(FlexFormService::class, $flexFormService);
    }

    private function prepareFlexFormServiceWithSubDataProcessorData(): void
    {
        $convertedFlexFormData = [
            'options' => [
                'hotels' => 0,
                'images' => '12',
            ],
        ];

        $flexFormService = $this->getMockBuilder(FlexFormService::class)->disableOriginalConstructor()->getMock();
        $flexFormService->method('convertFlexFormContentToArray')->with($this->getFlexFormStructure())->willReturn($convertedFlexFormData);
        GeneralUtility::setSingletonInstance(FlexFormService::class, $flexFormService);
    }

    private function prepareFlexFormServiceWithFalReferences(): void
    {
        $convertedFlexFormData = [
            'options' => [
                'hotels' => 0,
                'image' => 123,
            ],
        ];

        $flexFormService = $this->getMockBuilder(FlexFormService::class)->getMock();
        $flexFormService->method('convertFlexFormContentToArray')->with($this->getFlexFormStructure())->willReturn($convertedFlexFormData);
        GeneralUtility::setSingletonInstance(FlexFormService::class, $flexFormService);
    }
}
