<?php

declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Configuration\FlexForm;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FlexFormToolsTest extends UnitTestCase
{
    protected function tceFormsRemovedInMigrationDataProvider(): iterable
    {
        yield 'TCEforms removed recursively.' => [
            'dataStructure' => [
                'sheets' => [
                    'sDEF' => [
                        'ROOT' => [
                            'TCEforms' => [
                                'sheetTitle' => 'Sheet Title',
                            ],
                            'type' => 'array',
                            'el' => [
                                'input_1' => [
                                    'TCEforms' => [
                                        'label' => 'input_1',
                                        'config' => [
                                            'type' => 'input',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'expected' => [
                'sheets' => [
                    'sDEF' => [
                        'ROOT' => [
                            'sheetTitle' => 'Sheet Title',
                            'type' => 'array',
                            'el' => [
                                'input_1' => [
                                    'label' => 'input_1',
                                    'config' => [
                                        'type' => 'input',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider tceFormsRemovedInMigrationDataProvider
     * @test
     */
    public function tceFormsRemovedInMigration(array $dataStructure, array $expected): void
    {
        $eventDispatcher = new class() implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                return new \stdClass();
            }
        };
        $flexFormTools = new FlexFormTools($eventDispatcher);
        self::assertEquals($expected, $flexFormTools->removeElementTceFormsRecursive($dataStructure));
    }
}
