<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\CMS\Fluid\ViewHelpers\Form\CheckboxViewHelper;

/**
 * Test for the "Checkbox" Form view helper
 */
class CheckboxViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var CheckboxViewHelper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(CheckboxViewHelper::class, ['setErrorClassAttribute', 'getName', 'getValueAttribute', 'isObjectAccessorMode', 'getPropertyValue', 'registerFieldNameForFormTokenGeneration']);
        $this->arguments['property'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $mockTagBuilder = $this->getMock(TagBuilder::class, ['setTagName', 'addAttribute']);
        $mockTagBuilder->expects($this->once())->method('setTagName')->with('input');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfSpecified()
    {
        $mockTagBuilder = $this->getMock(TagBuilder::class, ['setTagName', 'addAttribute']);
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render(true);
    }

    /**
     * @test
     */
    public function renderIgnoresValueOfBoundPropertyIfCheckedIsSet()
    {
        $mockTagBuilder = $this->getMock(TagBuilder::class, ['setTagName', 'addAttribute']);
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(true));
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render(true);
        $this->viewHelper->render(false);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean()
    {
        $mockTagBuilder = $this->getMock(TagBuilder::class, ['setTagName', 'addAttribute']);
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(true));
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAppendsSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
    {
        $mockTagBuilder = $this->getMock(TagBuilder::class, ['setTagName', 'addAttribute']);
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo[]');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo[]');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue([]));
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArray()
    {
        $mockTagBuilder = $this->getMock(TagBuilder::class, ['setTagName', 'addAttribute']);
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo[]');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(['foo', 'bar', 'baz']));
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArrayObject()
    {
        $mockTagBuilder = $this->getMock(TagBuilder::class, ['setTagName', 'addAttribute']);
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo[]');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(new \ArrayObject(['foo', 'bar', 'baz'])));
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfBoundPropertyIsNotNull()
    {
        $mockTagBuilder = $this->getMock(TagBuilder::class, ['setTagName', 'addAttribute']);
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(new \stdClass()));
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeForListOfObjects()
    {
        $mockTagBuilder = $this->getMock(TagBuilder::class, ['setTagName', 'addAttribute']);
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo[]');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 2);
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $object3 = new \stdClass();

        $this->viewHelper->expects($this->any())->method('getName')->willReturn('foo');
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->willReturn(2);
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->willReturn(true);
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->willReturn([$object1, $object2, $object3]);
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $mockPersistenceManager = $this->getMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('getIdentifierByObject')->will($this->returnValueMap([
            [$object1, 1],
            [$object2, 2],
            [$object3, 3],
        ]));
        $this->viewHelper->_set('persistenceManager', $mockPersistenceManager);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }
}
