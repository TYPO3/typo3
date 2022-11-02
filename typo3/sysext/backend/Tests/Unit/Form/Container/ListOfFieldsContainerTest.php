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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Container;

use TYPO3\CMS\Backend\Form\Container\ListOfFieldsContainer;
use TYPO3\CMS\Backend\Form\Container\PaletteAndSingleContainer;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ListOfFieldsContainerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderDelegatesShowitemField(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $paletteAndSingleContainerMock = $this->createMock(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerMock->expects(self::atLeastOnce())->method('render')->withAnyParameters()->willReturn('');

        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField',
                    ],
                ],
            ],
            'fieldListToRender' => 'aField',
        ];

        $expected = $input;
        $expected['renderType'] = 'paletteAndSingleContainer';

        // Verify 'fieldArray' contains 'aField' since that is a showitem field of this type
        $expected['fieldsArray'] = [
            'aField;;',
        ];

        $nodeFactoryMock->method('create')->with($expected)->willReturn($paletteAndSingleContainerMock);
        (new ListOfFieldsContainer($nodeFactoryMock, $input))->render();
    }

    /**
     * @test
     */
    public function renderDelegatesShowitemFieldAndRemovesDuplicates(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $paletteAndSingleContainerMock = $this->createMock(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerMock->expects(self::atLeastOnce())->method('render')->withAnyParameters()->willReturn('');

        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => 'aField, bField;bLabel, cField',
                    ],
                ],
            ],
            'fieldListToRender' => 'aField, bField, aField',
        ];

        $expected = $input;
        $expected['renderType'] = 'paletteAndSingleContainer';
        // Duplicates are suppressed but label is kept
        $expected['fieldsArray'] = [
            'aField;;',
            'bField;bLabel;',
        ];

        $nodeFactoryMock->method('create')->with($expected)->willReturn($paletteAndSingleContainerMock);
        (new ListOfFieldsContainer($nodeFactoryMock, $input))->render();
    }

    /**
     * @test
     */
    public function renderDelegatesPaletteFields(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $paletteAndSingleContainerMock = $this->createMock(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerMock->expects(self::atLeastOnce())->method('render')->withAnyParameters()->willReturn('');

        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => '--palette--;;aPalette, --palette--;;anotherPalette',
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'aField',
                    ],
                    'anotherPalette' => [
                        'showitem' => 'bField;bLabel, cField',
                    ],
                ],
            ],
            'fieldListToRender' => 'aField, bField',
        ];

        $expected = $input;
        $expected['renderType'] = 'paletteAndSingleContainer';
        // Both palette fields are found
        $expected['fieldsArray'] = [
            'aField;;',
            'bField;bLabel;',
        ];

        $nodeFactoryMock->method('create')->with($expected)->willReturn($paletteAndSingleContainerMock);
        (new ListOfFieldsContainer($nodeFactoryMock, $input))->render();
    }

    /**
     * @test
     */
    public function renderRemovesNotExistingTypesField(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $paletteAndSingleContainerMock = $this->createMock(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerMock->expects(self::atLeastOnce())->method('render')->withAnyParameters()->willReturn('');

        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => '--palette--;;aPalette',
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'aField',
                    ],
                ],
            ],
            'fieldListToRender' => 'aField, iDontExist',
        ];

        $expected = $input;
        $expected['renderType'] = 'paletteAndSingleContainer';
        // Duplicates are suppressed but label is kept
        $expected['fieldsArray'] = [
            'aField;;',
        ];

        $nodeFactoryMock->method('create')->with($expected)->willReturn($paletteAndSingleContainerMock);
        (new ListOfFieldsContainer($nodeFactoryMock, $input))->render();
    }
}
