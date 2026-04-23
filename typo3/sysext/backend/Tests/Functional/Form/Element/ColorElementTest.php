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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\Element;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Element\ColorElement;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ColorElementTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['scheduler'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_core.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/tx_scheduler_task_group.csv');

        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->enablecolumns = ['deleted' => true];
        $GLOBALS['BE_USER']->setBeUserByUid(1);
        $GLOBALS['BE_USER']->initializeUserSessionManager();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
    }

    #[Test]
    public function renderRespectsConfiguredColorsWithResolvedLanguageLabels(): void
    {
        $schema = $this->get(TcaSchemaFactory::class)->get('tx_scheduler_task_group');
        $colorField = $schema->getField('color');
        $result = $this->getFormElementResult([
            'tableName' => 'tx_scheduler_task_group',
            'fieldName' => 'color',
            'processedTca' => [
                'columns' => [
                    'color' => [
                        'config' => $colorField->getConfiguration(),
                    ],
                ],
            ],
            'pageTsConfig' => [
                'colorPalettes.' => [
                    'colors.' => [
                        'funccolor1.' => [
                            'value' => '#123456',
                            'label' => 'TestColor1',
                        ],
                        'funccolor2.' => [
                            'value' => '#123457',
                            // This is just a randomly picked label that is unique and NOT already part of COLOR!
                            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.task_group',
                        ],
                    ],
                ],
            ],
            'databaseRow' => [
                'uid' => 1,
            ],
            'parameterArray' => [
                'itemFormElValue' => '',
                'itemFormElName' => 'color',
                'fieldConf' => [
                    'label' => 'foo',
                    'config' => $colorField->getConfiguration(),
                ],
            ],
        ]);

        self::assertStringNotContainsString('LLL:', $result['html']);
        self::assertStringContainsString('Yellow (#ffbf00)', $result['html']);
        self::assertStringContainsString('TYPO3 Orange (#FF8700)', $result['html']);
        self::assertStringContainsString('TestColor1 (#123456)', $result['html']);
        self::assertStringContainsString('Belongs to task group (#123457)', $result['html']);
    }

    #[Test]
    public function renderRespectsConfiguredColorPalettesWithResolvedLanguageLabels(): void
    {
        $schema = $this->get(TcaSchemaFactory::class)->get('tx_scheduler_task_group');
        $colorField = $schema->getField('color');
        $result = $this->getFormElementResult([
            'tableName' => 'tx_scheduler_task_group',
            'fieldName' => 'color',
            'processedTca' => [
                'columns' => [
                    'color' => [
                        'config' => $colorField->getConfiguration(),
                    ],
                ],
            ],
            'pageTsConfig' => [
                'TCEFORM.' => [
                    // This is where a palette is defined, which removes the other colors
                    'colorPalette' => 'functionalTest',
                ],

                'colorPalettes.' => [
                    'palettes.' => [
                        // This is the important part, we restrict the output to funccolor3+4, the other defined ones are not contained.
                        'functionalTest' => 'funccolor3, funccolor4',
                    ],
                    'colors.' => [
                        'funccolor1.' => [
                            'value' => '#123456',
                            'label' => 'TestColor1',
                        ],
                        'funccolor2.' => [
                            'value' => '#123457',
                            // This is just a randomly picked label that is unique and NOT already part of COLOR!
                            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.task_group',
                        ],
                        'funccolor3.' => [
                            'value' => '#123458',
                            'label' => 'TestColor3',
                        ],
                        'funccolor4.' => [
                            'value' => '#123459',
                            // This is just a randomly picked label that is unique and NOT already part of COLOR!
                            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.tasktype',
                        ],
                    ],
                ],
            ],
            'databaseRow' => [
                'uid' => 1,
            ],
            'parameterArray' => [
                'itemFormElValue' => '',
                'itemFormElName' => 'color',
                'fieldConf' => [
                    'label' => 'foo',
                    'config' => $colorField->getConfiguration(),
                ],
            ],
        ]);

        self::assertStringNotContainsString('LLL:', $result['html']);
        self::assertStringContainsString('Yellow (#ffbf00)', $result['html']);
        self::assertStringContainsString('TYPO3 Orange (#FF8700)', $result['html']);
        self::assertStringNotContainsString('TestColor1 (#123456)', $result['html']);
        self::assertStringNotContainsString('Belongs to task group (#123457)', $result['html']);
        self::assertStringContainsString('TestColor3 (#123458)', $result['html']);
        self::assertStringContainsString('Task type (#123459)', $result['html']);
    }

    #[Test]
    public function renderRespectsConfiguredColorsWithResolvedLanguageLabelsAndEmptyFallbacks(): void
    {
        $schema = $this->get(TcaSchemaFactory::class)->get('tx_scheduler_task_group');
        $colorField = $schema->getField('color');
        $result = $this->getFormElementResult([
            'tableName' => 'tx_scheduler_task_group',
            'fieldName' => 'color',
            'processedTca' => [
                'columns' => [
                    'color' => [
                        'config' => $colorField->getConfiguration(),
                    ],
                ],
            ],
            'pageTsConfig' => [
                'colorPalettes.' => [
                    'colors.' => [
                        'funccolor1.' => [
                            'value' => '#654321',
                            'label' => '',
                        ],
                        'funccolor2.' => [
                            'value' => '#654322',
                        ],
                        'funccolor3.' => [
                            'value' => '#654323',
                            // Invalid label notation resolves to empty label, not string "LLL:EXT:nopenope"!
                            'label' => 'LLL:EXT:nopenope',
                        ],
                        'funccolor4.' => [
                            'value' => '#654324',
                            // Testing placeholder interpolation to not fail
                            'label' => '%s654324',
                        ],
                        'funccolor5.' => [
                            'value' => '#654325',
                            // Resolved to an actual space character
                            'label' => ' ',
                        ],
                    ],
                ],
            ],
            'databaseRow' => [
                'uid' => 1,
            ],
            'parameterArray' => [
                'itemFormElValue' => '',
                'itemFormElName' => 'color',
                'fieldConf' => [
                    'label' => 'foo',
                    'config' => $colorField->getConfiguration(),
                ],
            ],
        ]);

        self::assertStringContainsString('&quot;color&quot;:&quot;#654321&quot;,&quot;label&quot;:&quot;#654321&quot;', $result['html']);
        self::assertStringContainsString('&quot;color&quot;:&quot;#654322&quot;,&quot;label&quot;:&quot;#654322&quot;', $result['html']);
        self::assertStringContainsString('&quot;color&quot;:&quot;#654323&quot;,&quot;label&quot;:&quot;#654323&quot;', $result['html']);
        self::assertStringContainsString('&quot;color&quot;:&quot;#654324&quot;,&quot;label&quot;:&quot;%s654324 (#654324)&quot;', $result['html']);
        self::assertStringContainsString('&quot;color&quot;:&quot;#654325&quot;,&quot;label&quot;:&quot;  (#654325)&quot;', $result['html']);
    }

    private function getFormElementResult(array $data): array
    {
        $node = $this->get(ColorElement::class);
        $node->setData($data);
        return $node->render();
    }
}
