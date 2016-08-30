<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for ForViewHelper
 */
class ForViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    protected function setUp()
    {
        parent::setUp();
        $this->templateVariableContainer = new \TYPO3\CMS\Fluid\Core\ViewHelper\TemplateVariableContainer([]);
        $this->renderingContext->injectTemplateVariableContainer($this->templateVariableContainer);

        $this->arguments['reverse'] = null;
        $this->arguments['key'] = '';
        $this->arguments['iteration'] = null;
    }

    /**
     * @test
     */
    public function renderExecutesTheLoopCorrectly()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);
        $this->arguments['each'] = [0, 1, 2, 3];
        $this->arguments['as'] = 'innerVariable';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as']);

        $expectedCallProtocol = [
            ['innerVariable' => 0],
            ['innerVariable' => 1],
            ['innerVariable' => 2],
            ['innerVariable' => 3]
        ];
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function renderPreservesKeys()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = ['key1' => 'value1', 'key2' => 'value2'];
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

        $expectedCallProtocol = [
            [
                'innerVariable' => 'value1',
                'someKey' => 'key1'
            ],
            [
                'innerVariable' => 'value2',
                'someKey' => 'key2'
            ]
        ];
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfObjectIsNull()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $this->arguments['each'] = null;
        $this->arguments['as'] = 'foo';

        $this->injectDependenciesIntoViewHelper($viewHelper);

        $this->assertEquals('', $viewHelper->render($this->arguments['each'], $this->arguments['as']));
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfObjectIsEmptyArray()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $this->arguments['each'] = [];
        $this->arguments['as'] = 'foo';

        $this->injectDependenciesIntoViewHelper($viewHelper);

        $this->assertEquals('', $viewHelper->render($this->arguments['each'], $this->arguments['as']));
    }

    /**
     * @test
     */
    public function renderIteratesElementsInReverseOrderIfReverseIsTrue()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = [0, 1, 2, 3];
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['reverse'] = true;

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse']);

        $expectedCallProtocol = [
            ['innerVariable' => 3],
            ['innerVariable' => 2],
            ['innerVariable' => 1],
            ['innerVariable' => 0]
        ];
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function renderPreservesKeysIfReverseIsTrue()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = ['key1' => 'value1', 'key2' => 'value2'];
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';
        $this->arguments['reverse'] = true;

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse']);

        $expectedCallProtocol = [
            [
                'innerVariable' => 'value2',
                'someKey' => 'key2'
            ],
            [
                'innerVariable' => 'value1',
                'someKey' => 'key1'
            ]
        ];
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function keyContainsNumericalIndexIfTheGivenArrayDoesNotHaveAKey()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = ['foo', 'bar', 'baz'];
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

        $expectedCallProtocol = [
            [
                'innerVariable' => 'foo',
                'someKey' => 0
            ],
            [
                'innerVariable' => 'bar',
                'someKey' => 1
            ],
            [
                'innerVariable' => 'baz',
                'someKey' => 2
            ]
        ];
        $this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function keyContainsNumericalIndexInAscendingOrderEvenIfReverseIsTrueIfTheGivenArrayDoesNotHaveAKey()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = ['foo', 'bar', 'baz'];
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';
        $this->arguments['reverse'] = true;

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse']);

        $expectedCallProtocol = [
            [
                'innerVariable' => 'baz',
                'someKey' => 0
            ],
            [
                'innerVariable' => 'bar',
                'someKey' => 1
            ],
            [
                'innerVariable' => 'foo',
                'someKey' => 2
            ]
        ];
        $this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionWhenPassingObjectsToEachThatAreNotTraversable()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();
        $object = new \stdClass();

        $this->arguments['each'] = $object;
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';
        $this->arguments['reverse'] = true;

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);
    }

    /**
     * @test
     */
    public function renderIteratesThroughElementsOfTraversableObjects()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = new \ArrayObject(['key1' => 'value1', 'key2' => 'value2']);
        $this->arguments['as'] = 'innerVariable';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as']);

        $expectedCallProtocol = [
            ['innerVariable' => 'value1'],
            ['innerVariable' => 'value2']
        ];
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function renderPreservesKeyWhenIteratingThroughElementsOfObjectsThatImplementIteratorInterface()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = new \ArrayIterator(['key1' => 'value1', 'key2' => 'value2']);
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

        $expectedCallProtocol = [
            [
                'innerVariable' => 'value1',
                'someKey' => 'key1'
            ],
            [
                'innerVariable' => 'value2',
                'someKey' => 'key2'
            ]
        ];
        $this->assertEquals($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function keyContainsTheNumericalIndexWhenIteratingThroughElementsOfObjectsOfTyeSplObjectStorage()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $splObjectStorageObject = new \SplObjectStorage();
        $object1 = new \stdClass();
        $splObjectStorageObject->attach($object1);
        $object2 = new \stdClass();
        $splObjectStorageObject->attach($object2, 'foo');
        $object3 = new \stdClass();
        $splObjectStorageObject->attach($object3, 'bar');

        $this->arguments['each'] = $splObjectStorageObject;
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['key'] = 'someKey';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key']);

        $expectedCallProtocol = [
            [
                'innerVariable' => $object1,
                'someKey' => 0
            ],
            [
                'innerVariable' => $object2,
                'someKey' => 1
            ],
            [
                'innerVariable' => $object3,
                'someKey' => 2
            ]
        ];
        $this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function iterationDataIsAddedToTemplateVariableContainerIfIterationArgumentIsSet()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $this->arguments['each'] = ['foo' => 'bar', 'FLOW3' => 'Fluid', 'TYPO3' => 'rocks'];
        $this->arguments['as'] = 'innerVariable';
        $this->arguments['iteration'] = 'iteration';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as'], $this->arguments['key'], $this->arguments['reverse'], $this->arguments['iteration']);

        $expectedCallProtocol = [
            [
                'innerVariable' => 'bar',
                'iteration' => [
                    'index' => 0,
                    'cycle' => 1,
                    'total' => 3,
                    'isFirst' => true,
                    'isLast' => false,
                    'isEven' => false,
                    'isOdd' => true
                ]
            ],
            [
                'innerVariable' => 'Fluid',
                'iteration' => [
                    'index' => 1,
                    'cycle' => 2,
                    'total' => 3,
                    'isFirst' => false,
                    'isLast' => false,
                    'isEven' => true,
                    'isOdd' => false
                ]
            ],
            [
                'innerVariable' => 'rocks',
                'iteration' => [
                    'index' => 2,
                    'cycle' => 3,
                    'total' => 3,
                    'isFirst' => false,
                    'isLast' => true,
                    'isEven' => false,
                    'isOdd' => true
                ]
            ]
        ];
        $this->assertSame($expectedCallProtocol, $viewHelperNode->callProtocol, 'The call protocol differs -> The for loop does not work as it should!');
    }

    /**
     * @test
     */
    public function iteratedItemsAreNotCountedIfIterationArgumentIsNotSet()
    {
        $viewHelper = new \TYPO3\CMS\Fluid\ViewHelpers\ForViewHelper();

        $viewHelperNode = new \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Fixtures\ConstraintSyntaxTreeNode($this->templateVariableContainer);

        $mockItems = $this->getMockBuilder(\ArrayObject::class)->setMethods(['count'])->disableOriginalConstructor()->getMock();
        $mockItems->expects($this->never())->method('count');
        $this->arguments['each'] = $mockItems;
        $this->arguments['as'] = 'innerVariable';

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($viewHelperNode);
        $viewHelper->render($this->arguments['each'], $this->arguments['as']);
    }
}
