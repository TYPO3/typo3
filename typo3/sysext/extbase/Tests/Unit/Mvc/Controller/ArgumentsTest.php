<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

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

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ArgumentsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function argumentsObjectIsOfScopePrototype()
    {
        $arguments1 = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments2 = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        self::assertNotSame($arguments1, $arguments2, 'The arguments object is not of scope prototype!');
    }

    /**
     * @test
     */
    public function addingAnArgumentManuallyWorks()
    {
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $newArgument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('argumentName1234', 'dummyValue');
        $arguments->addArgument($newArgument);
        self::assertSame($newArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
    }

    /**
     * @test
     */
    public function addingAnArgumentReplacesArgumentWithSameName()
    {
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $mockFirstArgument = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockFirstArgument->expects(self::any())->method('getName')->will(self::returnValue('argumentName1234'));
        $arguments->addArgument($mockFirstArgument);
        $mockSecondArgument = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockSecondArgument->expects(self::any())->method('getName')->will(self::returnValue('argumentName1234'));
        $arguments->addArgument($mockSecondArgument);
        self::assertSame($mockSecondArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
    }

    /**
     * @test
     */
    public function addNewArgumentProvidesFluentInterface()
    {
        $mockArgument = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class);
        $mockObjectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)->will(self::returnValue($mockArgument));
        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $newArgument = $arguments->addNewArgument('someArgument');
        self::assertSame($newArgument, $mockArgument);
    }

    /**
     * @test
     */
    public function addingArgumentThroughArrayAccessWorks()
    {
        $mockArgument = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->expects(self::any())->method('getName')->will(self::returnValue('argumentName1234'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments[] = $mockArgument;
        self::assertTrue($arguments->hasArgument('argumentName1234'), 'Added argument does not exist.');
        self::assertSame($mockArgument, $arguments->getArgument('argumentName1234'), 'Added and retrieved arguments are not the same.');
    }

    /**
     * @test
     */
    public function retrievingArgumentThroughArrayAccessWorks()
    {
        $mockArgument = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->expects(self::any())->method('getName')->will(self::returnValue('argumentName1234'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments[] = $mockArgument;
        self::assertSame($mockArgument, $arguments['argumentName1234'], 'Argument retrieved by array access is not the one we added.');
    }

    /**
     * @test
     */
    public function getArgumentWithNonExistingArgumentNameThrowsException()
    {
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        try {
            $arguments->getArgument('someArgument');
            self::fail('getArgument() did not throw an exception although the specified argument does not exist.');
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException $exception) {
        }
    }

    /**
     * @test
     */
    public function issetReturnsCorrectResult()
    {
        $mockArgument = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->expects(self::any())->method('getName')->will(self::returnValue('argumentName1234'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        self::assertFalse(isset($arguments['argumentName1234']), 'isset() did not return FALSE.');
        $arguments[] = $mockArgument;
        self::assertTrue(isset($arguments['argumentName1234']), 'isset() did not return TRUE.');
    }

    /**
     * @test
     */
    public function getArgumentNamesReturnsNamesOfAddedArguments()
    {
        $mockArgument1 = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument1->expects(self::any())->method('getName')->will(self::returnValue('argumentName1'));
        $mockArgument2 = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument2->expects(self::any())->method('getName')->will(self::returnValue('argumentName2'));
        $mockArgument3 = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument3->expects(self::any())->method('getName')->will(self::returnValue('argumentName3'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments[] = $mockArgument1;
        $arguments[] = $mockArgument2;
        $arguments[] = $mockArgument3;
        $expectedArgumentNames = ['argumentName1', 'argumentName2', 'argumentName3'];
        self::assertEquals($expectedArgumentNames, $arguments->getArgumentNames(), 'Returned argument names were not as expected.');
    }

    /**
     * @test
     */
    public function getArgumentShortNamesReturnsShortNamesOfAddedArguments()
    {
        $mockArgument1 = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName', 'getShortName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument1->expects(self::any())->method('getName')->will(self::returnValue('argumentName1'));
        $mockArgument1->expects(self::any())->method('getShortName')->will(self::returnValue('a'));
        $mockArgument2 = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName', 'getShortName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument2->expects(self::any())->method('getName')->will(self::returnValue('argumentName2'));
        $mockArgument2->expects(self::any())->method('getShortName')->will(self::returnValue('b'));
        $mockArgument3 = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName', 'getShortName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument3->expects(self::any())->method('getName')->will(self::returnValue('argumentName3'));
        $mockArgument3->expects(self::any())->method('getShortName')->will(self::returnValue('c'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments[] = $mockArgument1;
        $arguments[] = $mockArgument2;
        $arguments[] = $mockArgument3;
        $expectedShortNames = ['a', 'b', 'c'];
        self::assertEquals($expectedShortNames, $arguments->getArgumentShortNames(), 'Returned argument short names were not as expected.');
    }

    /**
     * @test
     */
    public function addNewArgumentCreatesAndAddsNewArgument()
    {
        $mockArgument = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->expects(self::any())->method('getName')->will(self::returnValue('dummyName'));
        $mockObjectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)->will(self::returnValue($mockArgument));
        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $addedArgument = $arguments->addNewArgument('dummyName');
        self::assertInstanceOf(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, $addedArgument, 'addNewArgument() either did not add a new argument or did not return it.');
        $retrievedArgument = $arguments['dummyName'];
        self::assertSame($addedArgument, $retrievedArgument, 'The added and the retrieved argument are not the same.');
    }

    /**
     * @test
     */
    public function addNewArgumentAssumesTextDataTypeByDefault()
    {
        $mockArgument = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->expects(self::any())->method('getName')->will(self::returnValue('dummyName'));
        $mockObjectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, 'dummyName', 'Text')->will(self::returnValue($mockArgument));
        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $arguments->addNewArgument('dummyName');
    }

    /**
     * @test
     */
    public function addNewArgumentCanAddArgumentsMarkedAsRequired()
    {
        $mockArgument = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName', 'setRequired'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->expects(self::once())->method('getName')->will(self::returnValue('dummyName'));
        $mockArgument->expects(self::once())->method('setRequired')->with(true);
        $mockObjectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, 'dummyName', 'Text')->will(self::returnValue($mockArgument));
        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $arguments->addNewArgument('dummyName', 'Text', true);
    }

    /**
     * @test
     */
    public function addNewArgumentCanAddArgumentsMarkedAsOptionalWithDefaultValues()
    {
        $mockArgument = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName', 'setRequired', 'setDefaultValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->expects(self::once())->method('getName')->will(self::returnValue('dummyName'));
        $mockArgument->expects(self::once())->method('setRequired')->with(false);
        $mockArgument->expects(self::once())->method('setDefaultValue')->with('someDefaultValue');
        $mockObjectManager = $this->createMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, 'dummyName', 'Text')->will(self::returnValue($mockArgument));
        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $arguments->addNewArgument('dummyName', 'Text', false, 'someDefaultValue');
    }

    /**
     * @test
     */
    public function callingInvalidMethodThrowsException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1210858451);
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments->nonExistingMethod();
    }

    /**
     * @test
     */
    public function removeAllClearsAllArguments()
    {
        $mockArgument1 = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName', 'getShortName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument1->expects(self::any())->method('getName')->will(self::returnValue('argumentName1'));
        $mockArgument2 = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)
            ->setMethods(['getName', 'getShortName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument2->expects(self::any())->method('getName')->will(self::returnValue('argumentName2'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments[] = $mockArgument1;
        $arguments[] = $mockArgument2;
        self::assertTrue($arguments->hasArgument('argumentName2'));
        $arguments->removeAll();
        self::assertFalse($arguments->hasArgument('argumentName2'));
    }
}
