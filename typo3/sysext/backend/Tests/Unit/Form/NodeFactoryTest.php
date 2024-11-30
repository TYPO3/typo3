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

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Element\SelectSingleElement;
use TYPO3\CMS\Backend\Form\Element\SelectTreeElement;
use TYPO3\CMS\Backend\Form\Element\UnknownElement;
use TYPO3\CMS\Backend\Form\Exception;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeElements\BarElement;
use TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeElements\FooElement;
use TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeResolvers\BarResolver;
use TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeResolvers\FooResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class NodeFactoryTest extends UnitTestCase
{
    #[Test]
    public function constructThrowsExceptionIfOverrideMissesNodeNameKey(): void
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

    #[Test]
    public function constructThrowsExceptionIfOverrideMissesPriorityKey(): void
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

    #[Test]
    public function constructThrowsExceptionIfOverrideMissesClassKey(): void
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

    #[Test]
    public function constructThrowsExceptionIfOverridePriorityIsLowerThanZero(): void
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
    #[Test]
    public function constructThrowsExceptionIfOverridePriorityIsHigherThanHundred(): void
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

    #[Test]
    public function constructorThrowsExceptionIfOverrideTwoNodesWithSamePriorityAndSameNodeNameAreRegistered(): void
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

    #[Test]
    public function constructThrowsExceptionIfResolverMissesNodeNameKey(): void
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

    #[Test]
    public function constructThrowsExceptionIfResolverMissesPriorityKey(): void
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

    #[Test]
    public function constructThrowsExceptionIfResolverMissesClassKey(): void
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

    #[Test]
    public function constructThrowsExceptionIfResolverPriorityIsLowerThanZero(): void
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
    #[Test]
    public function constructThrowsExceptionIfResolverPriorityIsHigherThanHundred(): void
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

    #[Test]
    public function constructorThrowsExceptionIfResolverTwoNodesWithSamePriorityAndSameNodeNameAreRegistered(): void
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

    #[Test]
    #[DoesNotPerformAssertions]
    public function constructorThrowsNoExceptionIfResolverWithSamePriorityButDifferentNodeNameAreRegistered(): void
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

    #[Test]
    public function createThrowsExceptionIfRenderTypeIsNotGiven(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1431452406);
        $subject = new NodeFactory();
        $subject->create([]);
    }

    #[Test]
    public function createReturnsInstanceOfUnknownElementIfTypeIsNotRegistered(): void
    {
        $unknownElementMock = $this->createMock(UnknownElement::class);
        GeneralUtility::addInstance(UnknownElement::class, $unknownElementMock);
        $subject = new NodeFactory();
        self::assertSame($unknownElementMock, $subject->create(['renderType' => 'foo']));
    }

    #[Test]
    public function createReturnsInstanceOfSelectTreeElementIfNeeded(): void
    {
        $data = [
            'type' => 'select',
            'renderType' => 'selectTree',
        ];
        $selectTreeElementMock = $this->createMock(SelectTreeElement::class);
        GeneralUtility::addInstance(SelectTreeElement::class, $selectTreeElementMock);
        $subject = new NodeFactory();
        self::assertSame($selectTreeElementMock, $subject->create($data));
    }

    #[Test]
    public function createReturnsInstanceOfSelectSingleElementIfNeeded(): void
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
        $selectSingleElementMock = $this->createMock(SelectSingleElement::class);
        GeneralUtility::addInstance(SelectSingleElement::class, $selectSingleElementMock);
        self::assertSame($selectSingleElementMock, $subject->create($data));
    }

    #[Test]
    public function createInstantiatesNewRegisteredElement(): void
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

    #[Test]
    public function createInstantiatesElementRegisteredWithHigherPriorityWithOneGivenOrder(): void
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

    #[Test]
    public function createInstantiatesElementRegisteredWithHigherPriorityWithOtherGivenOrder(): void
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

    #[Test]
    public function createInstantiatesResolverWithHighestPriorityFirstWithOneGivenOrder(): void
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

    #[Test]
    public function createInstantiatesResolverWithHighestPriorityFirstWithOtherGivenOrder(): void
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

    #[Test]
    public function createInstantiatesNodeClassReturnedByResolver(): void
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
