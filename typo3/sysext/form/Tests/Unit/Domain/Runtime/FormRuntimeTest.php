<?php
namespace TYPO3\CMS\Form\Tests\Unit\Domain\Runtime;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Domain\Runtime\FormState;

/**
 * Test case
 */
class FormRuntimeTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    /**
     * Set up
     */
    public function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
    }

    /**
     * Tear down
     */
    public function tearDown()
    {
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfFormDefinitionReturnsNoRendererClassName()
    {
        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, [
            'isAfterLastPage'
        ], [], '', false);

        $mockPage = $this->getAccessibleMock(Page::class, [
            'getIndex'
        ], [], '', false);

        $mockFormState = $this->getAccessibleMock(FormState::class, [
            'dummy'
        ], [], '', false);

        $mockFormDefinition = $this->getAccessibleMock(FormDefinition::class, [
            'getRendererClassName',
            'getIdentifier'
        ], [], '', false);

        $mockPage
            ->expects($this->any())
            ->method('getIndex')
            ->willReturn(1);

        $mockFormDefinition
            ->expects($this->any())
            ->method('getRendererClassName')
            ->willReturn(null);

        $mockFormDefinition
            ->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('text-1');

        $mockFormRuntime
            ->expects($this->any())
            ->method('isAfterLastPage')
            ->willReturn(false);

        $mockFormRuntime->_set('formState', $mockFormState);
        $mockFormRuntime->_set('currentPage', $mockPage);
        $mockFormRuntime->_set('formDefinition', $mockFormDefinition);

        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1326095912);

        $mockFormRuntime->_call('render');
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfRendererClassNameInstanceDoesNotImplementRendererInterface()
    {
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, [
            'isAfterLastPage'
        ], [], '', false);

        $mockPage = $this->getAccessibleMock(Page::class, [
            'getIndex'
        ], [], '', false);

        $mockFormState = $this->getAccessibleMock(FormState::class, [
            'dummy'
        ], [], '', false);

        $mockFormDefinition = $this->getAccessibleMock(FormDefinition::class, [
            'getRendererClassName',
            'getIdentifier'
        ], [], '', false);

        $mockPage
            ->expects($this->any())
            ->method('getIndex')
            ->willReturn(1);

        $mockFormDefinition
            ->expects($this->any())
            ->method('getRendererClassName')
            ->willReturn('fooRenderer');

        $mockFormDefinition
            ->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('text-1');

        $mockFormRuntime
            ->expects($this->any())
            ->method('isAfterLastPage')
            ->willReturn(false);

        $objectManagerProphecy
            ->get('fooRenderer')
            ->willReturn(new \stdClass);

        $mockFormRuntime->_set('formState', $mockFormState);
        $mockFormRuntime->_set('currentPage', $mockPage);
        $mockFormRuntime->_set('formDefinition', $mockFormDefinition);
        $mockFormRuntime->_set('objectManager', $objectManagerProphecy->reveal());

        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1326096024);

        $mockFormRuntime->_call('render');
    }
}
