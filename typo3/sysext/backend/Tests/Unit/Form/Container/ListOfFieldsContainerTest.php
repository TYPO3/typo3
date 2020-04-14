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

use Prophecy\Argument;
use TYPO3\CMS\Backend\Form\Container\ListOfFieldsContainer;
use TYPO3\CMS\Backend\Form\Container\PaletteAndSingleContainer;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ListOfFieldsContainerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderDelegatesShowitemField()
    {
        $nodeFactoryProphecy = $this->prophesize(NodeFactory::class);
        $paletteAndSingleContainerProphecy = $this->prophesize(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerProphecy->render(Argument::cetera())->shouldBeCalled()->willReturn('');

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

        $nodeFactoryProphecy->create($expected)->willReturn($paletteAndSingleContainerProphecy->reveal());
        (new ListOfFieldsContainer($nodeFactoryProphecy->reveal(), $input))->render();
    }

    /**
     * @test
     */
    public function renderDelegatesShowitemFieldAndRemovesDuplicates()
    {
        $nodeFactoryProphecy = $this->prophesize(NodeFactory::class);
        $paletteAndSingleContainerProphecy = $this->prophesize(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerProphecy->render(Argument::cetera())->shouldBeCalled()->willReturn('');

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

        $nodeFactoryProphecy->create($expected)->willReturn($paletteAndSingleContainerProphecy->reveal());
        (new ListOfFieldsContainer($nodeFactoryProphecy->reveal(), $input))->render();
    }

    /**
     * @test
     */
    public function renderDelegatesPaletteFields()
    {
        $nodeFactoryProphecy = $this->prophesize(NodeFactory::class);
        $paletteAndSingleContainerProphecy = $this->prophesize(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerProphecy->render(Argument::cetera())->shouldBeCalled()->willReturn('');

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
                    ]
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

        $nodeFactoryProphecy->create($expected)->willReturn($paletteAndSingleContainerProphecy->reveal());
        (new ListOfFieldsContainer($nodeFactoryProphecy->reveal(), $input))->render();
    }

    /**
     * @test
     */
    public function renderRemovesNotExistingTypesField()
    {
        $nodeFactoryProphecy = $this->prophesize(NodeFactory::class);
        $paletteAndSingleContainerProphecy = $this->prophesize(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerProphecy->render(Argument::cetera())->shouldBeCalled()->willReturn('');

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

        $nodeFactoryProphecy->create($expected)->willReturn($paletteAndSingleContainerProphecy->reveal());
        (new ListOfFieldsContainer($nodeFactoryProphecy->reveal(), $input))->render();
    }
}
