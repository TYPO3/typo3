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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Container\ListOfFieldsContainer;
use TYPO3\CMS\Backend\Form\Container\PaletteAndSingleContainer;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ListOfFieldsContainerTest extends UnitTestCase
{
    #[Test]
    public function renderDelegatesShowitemField(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $paletteAndSingleContainerMock = $this->createMock(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerMock->expects($this->atLeastOnce())->method('render')->withAnyParameters()->willReturn([]);

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

        $subject = new ListOfFieldsContainer();
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($input);
        $subject->render();
    }

    #[Test]
    public function renderDelegatesShowitemFieldAndRemovesDuplicates(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $paletteAndSingleContainerMock = $this->createMock(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerMock->expects($this->atLeastOnce())->method('render')->withAnyParameters()->willReturn([]);

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

        $subject = new ListOfFieldsContainer();
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($input);
        $subject->render();
    }

    #[Test]
    public function renderDelegatesPaletteFields(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $paletteAndSingleContainerMock = $this->createMock(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerMock->expects($this->atLeastOnce())->method('render')->withAnyParameters()->willReturn([]);

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

        $subject = new ListOfFieldsContainer();
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($input);
        $subject->render();
    }

    #[Test]
    public function renderRemovesNotExistingTypesField(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $paletteAndSingleContainerMock = $this->createMock(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerMock->expects($this->atLeastOnce())->method('render')->withAnyParameters()->willReturn([]);

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

        $subject = new ListOfFieldsContainer();
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($input);
        $subject->render();
    }

    #[Test]
    public function renderAddsHiddenFields(): void
    {
        $nodeFactoryMock = $this->createMock(NodeFactory::class);
        $paletteAndSingleContainerMock = $this->createMock(PaletteAndSingleContainer::class);
        $paletteAndSingleContainerMock->expects($this->atLeastOnce())->method('render')->withAnyParameters()->willReturn([]);

        $input = [
            'tableName' => 'aTable',
            'recordTypeValue' => 'aType',
            'processedTca' => [
                'types' => [
                    'aType' => [
                        'showitem' => '--palette--;;aPalette,uniqueField,hiddenField,--palette--;;bPalette,',
                    ],
                ],
                'palettes' => [
                    'aPalette' => [
                        'showitem' => 'aField',
                    ],
                    'bPalette' => [
                        'showitem' => 'hiddenInPalette',
                    ],
                ],
            ],
            'fieldListToRender' => 'aField,uniqueField',
            'hiddenFieldListToRender' => 'hiddenField,uniqueField,iDontExist,hiddenInPalette',
        ];

        $expected = $input;
        $expected['renderType'] = 'paletteAndSingleContainer';
        $expected['processedTca']['palettes']['hiddenFieldsPalette' . md5('hiddenField;;,hiddenInPalette;;')] = [
            'isHiddenPalette' => true,
            'showitem' => 'hiddenField;;,hiddenInPalette;;',
        ];
        // "uniqueField" is onl rendered once and "iDontExist" is not rendered at all
        $expected['fieldsArray'] = [
            'aField;;',
            'uniqueField;;',
            '--palette--;;' . 'hiddenFieldsPalette' . md5('hiddenField;;,hiddenInPalette;;'),
        ];

        $nodeFactoryMock->method('create')->with($expected)->willReturn($paletteAndSingleContainerMock);

        $subject = new ListOfFieldsContainer();
        $subject->injectNodeFactory($nodeFactoryMock);
        $subject->setData($input);
        $subject->render();
    }
}
