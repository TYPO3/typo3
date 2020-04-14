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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Factory;

use Prophecy\Argument;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Model\FormElements\Section;
use TYPO3\CMS\Form\Domain\Model\FormElements\UnknownFormElement;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ArrayFormFactoryTest extends UnitTestCase
{

    /**
     * @test
     */
    public function addNestedRenderableThrowsExceptionIfIdentifierIsMissing()
    {
        $this->expectException(IdentifierNotValidException::class);
        $this->expectExceptionCode(1329289436);

        $section = new Section('test', 'page');
        $arrayFormFactory = $this->getAccessibleMock(ArrayFormFactory::class, ['dummy']);

        $arrayFormFactory->_call('addNestedRenderable', [], $section);
    }

    /**
     * @test
     */
    public function addNestedRenderableSkipChildElementRenderingIfCompositeElementIsUnknown()
    {
        $unknownElement = new UnknownFormElement('test-2', 'test');

        $section = $this->prophesize(Section::class);
        $section->willBeConstructedWith(['test-1', 'Section']);
        $section->createElement(Argument::cetera())->willReturn($unknownElement);

        $arrayFormFactory = $this->getAccessibleMock(ArrayFormFactory::class, ['dummy']);

        $configuration = [
            'identifier' => 'test-3',
            'type' => 'Foo',
            'renderables' => [
                0 => [
                    'identifier' => 'test-4',
                ],
            ],
        ];

        $typeErrorExists = false;
        try {
            $arrayFormFactory->_call('addNestedRenderable', $configuration, $section->reveal());
        } catch (\TypeError $error) {
            $typeErrorExists = true;
        }
        self::assertFalse($typeErrorExists);
    }
}
