<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements;

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

use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\GridContainer;
use TYPO3\CMS\Form\Domain\Model\FormElements\GridContainerInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\GridRowInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class GridContainerTest extends UnitTestCase
{

    /**
     * @test
     */
    public function registerInFormIfPossibleThrowsTypeDefinitionNotValidExceptionIfAChildIsGridContainerInterface()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|GridContainerInterface $gridContainerInterfaceMock */
        $gridContainerInterfaceMock = $this->createMock(GridContainerInterface::class);
        $gridContainerInterfaceMock
            ->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('bar');

        /** @var \PHPUnit_Framework_MockObject_MockObject|GridContainer $gridContainerMock */
        $gridContainerMock = $this->getMockBuilder(GridContainer::class)
            ->setMethods(['getIdentifier', 'getElementsRecursively'])
            ->disableOriginalConstructor()
            ->getMock();

        $gridContainerMock
            ->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('foo');

        $gridContainerMock
            ->expects($this->any())
            ->method('getElementsRecursively')
            ->willReturn([$gridContainerInterfaceMock]);

        $this->assertSame([$gridContainerInterfaceMock], $gridContainerMock->getElementsRecursively());
        $this->assertTrue($gridContainerMock->getElementsRecursively()[0] instanceof GridContainerInterface);

        $this->expectException(TypeDefinitionNotValidException::class);
        $this->expectExceptionCode(1489412790);

        $gridContainerMock->registerInFormIfPossible();
    }

    /**
     * @test
     */
    public function addElementThrowsTypeDefinitionNotValidExceptionIfNotInstanceOfGridRowInterface()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormElementInterface $element */
        $element = $this->createMock(FormElementInterface::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|GridContainer $gridContainer */
        $gridContainer = $this->getMockBuilder(GridContainer::class)
            ->setMethods(['getElementsRecursively'])
            ->disableOriginalConstructor()
            ->getMock();

        $element
            ->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('foobar');

        $element
            ->expects($this->any())
            ->method('getType')
            ->willReturn('FormElementOrSomethingLikeThat');

        $gridContainer
            ->expects($this->any())
            ->method('getElementsRecursively')
            ->willReturn($element);

        $this->expectException(TypeDefinitionNotValidException::class);
        $this->expectExceptionCode(1489486301);

        $gridContainer->addElement($element);
    }

    /**
     * @test
     */
    public function addElementExpectedCallAddRenderableIfInstanceOfGridRowInterface()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|GridRowInterface $element */
        $element = $this->createMock(GridRowInterface::class);

        /** @var GridContainer|\PHPUnit_Framework_MockObject_MockObject $gridContainer */
        $gridContainer = $this->getAccessibleMockForAbstractClass(
            GridContainer::class,
            [],
            '',
            [],
            true,
            true,
            ['addRenderable']
        );

        $gridContainer->expects($this->once())->method('addRenderable');
        $gridContainer->addElement($element);
    }
}
