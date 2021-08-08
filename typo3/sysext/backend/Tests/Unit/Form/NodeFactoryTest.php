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

namespace TYPO3\CMS\Backend\Tests\Unit\Form;

use TYPO3\CMS\Backend\Form\Element\SelectSingleElement;
use TYPO3\CMS\Backend\Form\Element\SelectTreeElement;
use TYPO3\CMS\Backend\Form\Element\UnknownElement;
use TYPO3\CMS\Backend\Form\Exception;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeElements\BarElement;
use TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeElements\FooElement;
use TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeResolvers\BarResolver;
use TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeResolvers\FooResolver;
use TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeResolvers\InvalidNodeResolverClass;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class NodeFactoryTest extends UnitTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @test
     */
    public function constructThrowsExceptionIfOverrideMissesNodeNameKey()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1432207533);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            1433089391 => [
                'class' => 'foo',
                'priority' => 23,
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function constructThrowsExceptionIfOverrideMissesPriorityKey()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1432207533);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            1433089393 => [
                'nodeName' => 'foo',
                'class' => 'bar',
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function constructThrowsExceptionIfOverrideMissesClassKey()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1432207533);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            1433089392 => [
                'nodeName' => 'foo',
                'priority' => 23,
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function constructThrowsExceptionIfOverridePriorityIsLowerThanZero()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1432223531);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            1433089394 => [
                'nodeName' => 'foo',
                'class' => 'bar',
                'priority' => -23,
            ],
        ];
        new NodeFactory();
    }
    /**
     * @test
     */
    public function constructThrowsExceptionIfOverridePriorityIsHigherThanHundred()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1432223531);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            1433089395 => [
                'nodeName' => 'foo',
                'class' => 'bar',
                'priority' => 142,
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfOverrideTwoNodesWithSamePriorityAndSameNodeNameAreRegistered()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1432223893);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            1433089396 => [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => 'fooClass',
            ],
            1433089397 => [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => 'barClass',
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function constructThrowsExceptionIfResolverMissesNodeNameKey()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1433155522);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433154905 => [
                'class' => 'foo',
                'priority' => 23,
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function constructThrowsExceptionIfResolverMissesPriorityKey()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1433155522);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433154905 => [
                'nodeName' => 'foo',
                'class' => 'bar',
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function constructThrowsExceptionIfResolverMissesClassKey()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1433155522);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433154906 => [
                'nodeName' => 'foo',
                'priority' => 23,
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function constructThrowsExceptionIfResolverPriorityIsLowerThanZero()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1433155563);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433154907 => [
                'nodeName' => 'foo',
                'class' => 'bar',
                'priority' => -23,
            ],
        ];
        new NodeFactory();
    }
    /**
     * @test
     */
    public function constructThrowsExceptionIfResolverPriorityIsHigherThanHundred()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1433155563);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433154908 => [
                'nodeName' => 'foo',
                'class' => 'bar',
                'priority' => 142,
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfResolverTwoNodesWithSamePriorityAndSameNodeNameAreRegistered()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1433155705);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433154909 => [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => 'fooClass',
            ],
            1433154910 => [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => 'barClass',
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function constructorThrowsNoExceptionIfResolverWithSamePriorityButDifferentNodeNameAreRegistered()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433154909 => [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => 'fooClass',
            ],
            1433154910 => [
                'nodeName' => 'bar',
                'priority' => 20,
                'class' => 'barClass',
            ],
        ];
        new NodeFactory();
    }

    /**
     * @test
     */
    public function createThrowsExceptionIfRenderTypeIsNotGiven()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1431452406);
        $subject = new NodeFactory();
        $subject->create([]);
    }

    /**
     * @test
     */
    public function createThrowsExceptionIfNodeDoesNotImplementNodeInterface()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1431872546);
        $mockNode = new \stdClass();
        /** @var NodeFactory|\PHPUnit\Framework\MockObject\MockObject $mockSubject */
        $mockSubject = $this->getMockBuilder(NodeFactory::class)
            ->onlyMethods(['instantiate'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockSubject->expects(self::once())->method('instantiate')->willReturn($mockNode);
        $mockSubject->create(['renderType' => 'foo']);
    }

    /**
     * @test
     */
    public function createReturnsInstanceOfUnknownElementIfTypeIsNotRegistered()
    {
        $unknownElementProphecy = $this->prophesize(UnknownElement::class);
        $unknownElementRevelation = $unknownElementProphecy->reveal();
        GeneralUtility::addInstance(UnknownElement::class, $unknownElementRevelation);
        $subject = new NodeFactory();
        self::assertSame($unknownElementRevelation, $subject->create(['renderType' => 'foo']));
    }

    /**
     * @test
     */
    public function createReturnsInstanceOfSelectTreeElementIfNeeded()
    {
        $data = [
            'type' => 'select',
            'renderType' => 'selectTree',
        ];
        $selectTreeElementProphecy = $this->prophesize(SelectTreeElement::class);
        $selectTreeElementRevelation = $selectTreeElementProphecy->reveal();
        GeneralUtility::addInstance(SelectTreeElement::class, $selectTreeElementRevelation);
        $subject = new NodeFactory();
        self::assertSame($selectTreeElementRevelation, $subject->create($data));
    }

    /**
     * @test
     */
    public function createReturnsInstanceOfSelectSingleElementIfNeeded()
    {
        $data = [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'parameterArray' => [
                'fieldConf' => [
                    'config' => [],
                ],
            ],
        ];
        $subject = new NodeFactory();
        $selectSingleElementProphecy = $this->prophesize(SelectSingleElement::class);
        $selectSingleElementRevelation = $selectSingleElementProphecy->reveal();
        GeneralUtility::addInstance(SelectSingleElement::class, $selectSingleElementRevelation);
        self::assertSame($selectSingleElementRevelation, $subject->create($data));
    }

    /**
     * @test
     */
    public function createInstantiatesNewRegisteredElement()
    {
        $data = ['renderType' => 'foo'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => FooElement::class,
            ],
        ];

        $subject = new NodeFactory();
        self::assertInstanceOf(FooElement::class, ($subject->create($data)));
    }

    /**
     * @test
     */
    public function createInstantiatesElementRegisteredWithHigherPriorityWithOneGivenOrder()
    {
        $data = ['renderType' => 'foo'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            1433089467 => [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => FooElement::class,
            ],
            1433089468 => [
                'nodeName' => 'foo',
                'priority' => 30,
                'class' => BarElement::class,
            ],
        ];
        $subject = new NodeFactory();
        self::assertInstanceOf(BarElement::class, ($subject->create($data)));
    }

    /**
     * @test
     */
    public function createInstantiatesElementRegisteredWithHigherPriorityWithOtherGivenOrder()
    {
        $data = ['renderType' => 'foo'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            1433089469 => [
                'nodeName' => 'foo',
                'priority' => 30,
                'class' => FooElement::class,
            ],
            1433089470 => [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => BarElement::class,
            ],
        ];
        $subject = new NodeFactory();
        self::assertInstanceOf(FooElement::class, ($subject->create($data)));
    }

    /**
     * @test
     */
    public function createThrowsExceptionIfResolverDoesNotImplementNodeResolverInterface()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1433157422);
        $data = ['renderType' => 'foo'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433156887 => [
                'nodeName' => 'foo',
                'priority' => 10,
                'class' => InvalidNodeResolverClass::class,
            ],
        ];
        $subject = new NodeFactory();
        $subject->create($data);
    }

    /**
     * @test
     */
    public function createInstantiatesResolverWithHighestPriorityFirstWithOneGivenOrder()
    {
        $data = ['renderType' => 'foo'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => FooElement::class,
            ],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433156887 => [
                'nodeName' => 'foo',
                'priority' => 10,
                'class' => FooResolver::class,
            ],
            1433156888 => [
                'nodeName' => 'foo',
                'priority' => 30,
                'class' => BarResolver::class,
            ],
        ];
        $subject = new NodeFactory();
        self::assertInstanceOf(BarElement::class, ($subject->create($data)));
    }

    /**
     * @test
     */
    public function createInstantiatesResolverWithHighestPriorityFirstWithOtherGivenOrder()
    {
        $data = ['renderType' => 'foo'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => FooElement::class,
            ],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433156887 => [
                'nodeName' => 'foo',
                'priority' => 30,
                'class' => BarResolver::class,
            ],
            1433156888 => [
                'nodeName' => 'foo',
                'priority' => 10,
                'class' => FooResolver::class,
            ],
        ];
        $subject = new NodeFactory();
        self::assertInstanceOf(BarElement::class, ($subject->create($data)));
    }

    /**
     * @test
     */
    public function createInstantiatesNodeClassReturnedByResolver()
    {
        $data = ['renderType' => 'foo'];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'] = [
            [
                'nodeName' => 'foo',
                'priority' => 20,
                'class' => FooElement::class,
            ],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'] = [
            1433156887 => [
                'nodeName' => 'foo',
                'priority' => 30,
                'class' => BarResolver::class,
            ],
        ];
        $subject = new NodeFactory();
        self::assertInstanceOf(BarElement::class, ($subject->create($data)));
    }
}
