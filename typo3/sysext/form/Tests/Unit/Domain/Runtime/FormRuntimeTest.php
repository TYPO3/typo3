<?php

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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Runtime;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\Page;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Domain\Runtime\FormState;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class FormRuntimeTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function renderThrowsExceptionIfFormDefinitionReturnsNoRendererClassName(): void
    {
        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, [
            'isAfterLastPage', 'processVariants'
        ], [], '', false);

        $mockPage = $this->getMockBuilder(Page::class)->onlyMethods([
            'getIndex'
        ])->disableOriginalConstructor()->getMock();

        $mockFormState = $this->getMockBuilder(FormState::class)->disableOriginalConstructor()->getMock();

        $mockFormDefinition = $this->getMockBuilder(FormDefinition::class)->onlyMethods([
            'getRendererClassName',
            'getIdentifier'
        ])->disableOriginalConstructor()->getMock();

        $mockPage
            ->method('getIndex')
            ->willReturn(1);

        $mockFormDefinition
            ->method('getRendererClassName')
            ->willReturn('');

        $mockFormDefinition
            ->method('getIdentifier')
            ->willReturn('text-1');

        $mockFormRuntime
            ->method('isAfterLastPage')
            ->willReturn(false);

        $mockFormRuntime
            ->method('processVariants')
            ->willReturn(null);

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
    public function renderThrowsExceptionIfRendererClassNameInstanceDoesNotImplementRendererInterface(): void
    {
        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        GeneralUtility::setSingletonInstance(ObjectManager::class, $objectManagerProphecy->reveal());

        $mockFormRuntime = $this->getAccessibleMock(FormRuntime::class, [
            'isAfterLastPage', 'processVariants'
        ], [], '', false);

        $mockPage = $this->getMockBuilder(Page::class)->onlyMethods([
            'getIndex'
        ])->disableOriginalConstructor()->getMock();

        $mockFormState = $this->getMockBuilder(FormState::class)->disableOriginalConstructor()->getMock();

        $mockFormDefinition = $this->getMockBuilder(FormDefinition::class)->onlyMethods([
            'getRendererClassName',
            'getIdentifier'
        ])->disableOriginalConstructor()->getMock();

        $mockPage
            ->method('getIndex')
            ->willReturn(1);

        $mockFormDefinition
            ->method('getRendererClassName')
            ->willReturn('fooRenderer');

        $mockFormDefinition
            ->method('getIdentifier')
            ->willReturn('text-1');

        $mockFormRuntime
            ->method('isAfterLastPage')
            ->willReturn(false);

        $mockFormRuntime
            ->method('processVariants')
            ->willReturn(null);

        $objectManagerProphecy
            ->get('fooRenderer')
            ->willReturn(new \stdClass());

        $mockFormRuntime->_set('formState', $mockFormState);
        $mockFormRuntime->_set('currentPage', $mockPage);
        $mockFormRuntime->_set('formDefinition', $mockFormDefinition);
        $mockFormRuntime->_set('objectManager', $objectManagerProphecy->reveal());

        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1326096024);

        $mockFormRuntime->_call('render');
    }
}
