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

namespace TYPO3\CMS\Scheduler\Tests\Functional\Form\Element;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Scheduler\Form\Element\TimingOptionsElement;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for TimingOptionsElement
 */
final class TimingOptionsElementTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/scheduler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function renderAppliesOverrideFieldTcaConfiguration(): void
    {
        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'execution_details',
            'command' => 'edit',
            'databaseRow' => [
                'uid' => 1,
            ],
            'processedTca' => [
                'columns' => [
                    'execution_details' => [
                        'config' => [
                            'type' => 'json',
                            'renderType' => 'schedulerTimingOptions',
                        ],
                    ],
                ],
            ],
            'parameterArray' => [
                'itemFormElName' => 'data[tx_scheduler_task][1][execution_details]',
                'itemFormElValue' => [],
                'fieldConf' => [
                    'config' => [
                        'overrideFieldTca' => [
                            'frequency' => [
                                'config' => [
                                    'size' => 60,
                                    'valuePicker' => [
                                        'items' => [
                                            ['value' => '0 2 * * *', 'label' => 'Daily at 2am'],
                                            ['value' => '0 */6 * * *', 'label' => 'Every 6 hours'],
                                        ],
                                    ],
                                ],
                            ],
                            'multiple' => [
                                'description' => 'Custom description for parallel execution',
                            ],
                            'runningType' => [
                                'config' => [
                                    'items' => [
                                        ['label' => 'Single run', 'value' => 1],
                                        ['label' => 'Recurring', 'value' => 2],
                                        ['label' => 'Custom type', 'value' => 3],
                                    ],
                                ],
                            ],
                            'start' => [
                                'config' => [
                                    'placeholder' => 'Select start date',
                                ],
                            ],
                            'end' => [
                                'config' => [
                                    'placeholder' => 'Select end date',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'request' => (new ServerRequest('http://localhost/typo3/', 'GET'))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
                ->withAttribute('route', new Route('/test', [])),
        ];

        $element = $this->get(TimingOptionsElement::class);
        $element->setData($data);
        $result = $element->render();

        // Verify the result structure
        self::assertArrayHasKey('html', $result);
        self::assertIsString($result['html']);

        // Verify timing options element is rendered
        self::assertStringContainsString('typo3-formengine-element-timing-options', $result['html']);

        // Verify all timing fields are present
        self::assertStringContainsString('t3js-timing-options-frequency', $result['html']);
        self::assertStringContainsString('t3js-timing-options-parallel', $result['html']);
        self::assertStringContainsString('t3js-timing-options-runningType', $result['html']);
        self::assertStringContainsString('t3js-timing-options-start', $result['html']);
        self::assertStringContainsString('t3js-timing-options-end', $result['html']);

        // Verify that custom valuePicker options are present
        self::assertStringContainsString('Daily at 2am', $result['html']);
        self::assertStringContainsString('Every 6 hours', $result['html']);
        self::assertStringContainsString('0 2 * * *', $result['html']);
        self::assertStringContainsString('0 */6 * * *', $result['html']);

        // Verify that custom description is applied
        self::assertStringContainsString('Custom description for parallel execution', $result['html']);

        // Verify that custom running type options are present
        self::assertStringContainsString('Custom type', $result['html']);

        // Verify that placeholders are applied
        self::assertStringContainsString('Select start date', $result['html']);
        self::assertStringContainsString('Select end date', $result['html']);
    }

    #[Test]
    public function renderWorksWithoutOverrideFieldTca(): void
    {
        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'execution_details',
            'command' => 'edit',
            'databaseRow' => [
                'uid' => 1,
            ],
            'processedTca' => [
                'columns' => [
                    'execution_details' => [
                        'config' => [
                            'type' => 'json',
                            'renderType' => 'schedulerTimingOptions',
                        ],
                    ],
                ],
            ],
            'parameterArray' => [
                'itemFormElName' => 'data[tx_scheduler_task][1][execution_details]',
                'itemFormElValue' => [],
                'fieldConf' => [
                    'config' => [],
                ],
            ],
            'request' => (new ServerRequest('http://localhost/typo3/', 'GET'))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
                ->withAttribute('route', new Route('/test', [])),
        ];

        $element = $this->get(TimingOptionsElement::class);
        $element->setData($data);
        $result = $element->render();

        // Verify the element renders without errors when no overrides are provided
        self::assertArrayHasKey('html', $result);
        self::assertStringContainsString('typo3-formengine-element-timing-options', $result['html']);

        // Verify all default timing fields are still present
        self::assertStringContainsString('t3js-timing-options-frequency', $result['html']);
        self::assertStringContainsString('t3js-timing-options-parallel', $result['html']);
        self::assertStringContainsString('t3js-timing-options-runningType', $result['html']);
        self::assertStringContainsString('t3js-timing-options-start', $result['html']);
        self::assertStringContainsString('t3js-timing-options-end', $result['html']);
    }

    #[Test]
    public function renderWithPartialOverrideFieldTca(): void
    {
        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'execution_details',
            'command' => 'edit',
            'databaseRow' => [
                'uid' => 1,
            ],
            'processedTca' => [
                'columns' => [
                    'execution_details' => [
                        'config' => [
                            'type' => 'json',
                            'renderType' => 'schedulerTimingOptions',
                        ],
                    ],
                ],
            ],
            'parameterArray' => [
                'itemFormElName' => 'data[tx_scheduler_task][1][execution_details]',
                'itemFormElValue' => [],
                'fieldConf' => [
                    'config' => [
                        'overrideFieldTca' => [
                            // Only override frequency field
                            'frequency' => [
                                'description' => 'My custom description',
                            ],
                        ],
                    ],
                ],
            ],
            'request' => (new ServerRequest('http://localhost/typo3/', 'GET'))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
                ->withAttribute('route', new Route('/test', [])),
        ];

        $element = $this->get(TimingOptionsElement::class);
        $element->setData($data);
        $result = $element->render();

        // Verify partial overrides work correctly
        self::assertArrayHasKey('html', $result);

        // Verify the custom description is applied
        self::assertStringContainsString('My custom description', $result['html']);

        // Verify other fields are still rendered with defaults
        self::assertStringContainsString('t3js-timing-options-parallel', $result['html']);
        self::assertStringContainsString('t3js-timing-options-runningType', $result['html']);
    }

    #[Test]
    public function renderHandlesExistingExecutionDetails(): void
    {
        $executionDetails = [
            'runningType' => 2, // Recurring
            'multiple' => true,
            'start' => 1700000000,
            'end' => 1800000000,
            'frequency' => '0 2 * * *',
        ];

        $data = [
            'tableName' => 'tx_scheduler_task',
            'fieldName' => 'execution_details',
            'command' => 'edit',
            'databaseRow' => [
                'uid' => 1,
            ],
            'processedTca' => [
                'columns' => [
                    'execution_details' => [
                        'config' => [
                            'type' => 'json',
                            'renderType' => 'schedulerTimingOptions',
                        ],
                    ],
                ],
            ],
            'parameterArray' => [
                'itemFormElName' => 'data[tx_scheduler_task][1][execution_details]',
                'itemFormElValue' => $executionDetails,
                'fieldConf' => [
                    'config' => [
                        'overrideFieldTca' => [
                            'frequency' => [
                                'config' => [
                                    'valuePicker' => [
                                        'items' => [
                                            ['value' => '0 2 * * *', 'label' => 'Daily at 2am'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'request' => (new ServerRequest('http://localhost/typo3/', 'GET'))
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
                ->withAttribute('route', new Route('/test', [])),
        ];

        $element = $this->get(TimingOptionsElement::class);
        $element->setData($data);
        $result = $element->render();

        // Verify the element renders with existing data and applies overrides
        self::assertArrayHasKey('html', $result);
        self::assertStringContainsString('typo3-formengine-element-timing-options', $result['html']);

        // Verify the frequency value is pre-filled
        self::assertStringContainsString('value="0 2 * * *"', $result['html']);

        // Verify the valuePicker override is still applied
        self::assertStringContainsString('Daily at 2am', $result['html']);
    }
}
