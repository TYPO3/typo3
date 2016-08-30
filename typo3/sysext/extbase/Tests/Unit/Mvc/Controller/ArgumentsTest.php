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

/**
 * Test case
 */
class ArgumentsTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function argumentsObjectIsOfScopePrototype()
    {
        $arguments1 = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments2 = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $this->assertNotSame($arguments1, $arguments2, 'The arguments object is not of scope prototype!');
    }

    /**
     * @test
     */
    public function addingAnArgumentManuallyWorks()
    {
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $newArgument = new \TYPO3\CMS\Extbase\Mvc\Controller\Argument('argumentName1234', 'dummyValue');
        $arguments->addArgument($newArgument);
        $this->assertSame($newArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
    }

    /**
     * @test
     */
    public function addingAnArgumentReplacesArgumentWithSameName()
    {
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $mockFirstArgument = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);
        $mockFirstArgument->expects($this->any())->method('getName')->will($this->returnValue('argumentName1234'));
        $arguments->addArgument($mockFirstArgument);
        $mockSecondArgument = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);
        $mockSecondArgument->expects($this->any())->method('getName')->will($this->returnValue('argumentName1234'));
        $arguments->addArgument($mockSecondArgument);
        $this->assertSame($mockSecondArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
    }

    /**
     * @test
     */
    public function addNewArgumentProvidesFluentInterface()
    {
        $mockArgument = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, [], [], '', false);
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)->will($this->returnValue($mockArgument));
        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $newArgument = $arguments->addNewArgument('someArgument');
        $this->assertSame($newArgument, $mockArgument);
    }

    /**
     * @test
     */
    public function addingArgumentThroughArrayAccessWorks()
    {
        $mockArgument = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);
        $mockArgument->expects($this->any())->method('getName')->will($this->returnValue('argumentName1234'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments[] = $mockArgument;
        $this->assertTrue($arguments->hasArgument('argumentName1234'), 'Added argument does not exist.');
        $this->assertSame($mockArgument, $arguments->getArgument('argumentName1234'), 'Added and retrieved arguments are not the same.');
    }

    /**
     * @test
     */
    public function retrievingArgumentThroughArrayAccessWorks()
    {
        $mockArgument = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);
        $mockArgument->expects($this->any())->method('getName')->will($this->returnValue('argumentName1234'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments[] = $mockArgument;
        $this->assertSame($mockArgument, $arguments['argumentName1234'], 'Argument retrieved by array access is not the one we added.');
    }

    /**
     * @test
     */
    public function getArgumentWithNonExistingArgumentNameThrowsException()
    {
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        try {
            $arguments->getArgument('someArgument');
            $this->fail('getArgument() did not throw an exception although the specified argument does not exist.');
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException $exception) {
        }
    }

    /**
     * @test
     */
    public function issetReturnsCorrectResult()
    {
        $mockArgument = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);
        $mockArgument->expects($this->any())->method('getName')->will($this->returnValue('argumentName1234'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $this->assertFalse(isset($arguments['argumentName1234']), 'isset() did not return FALSE.');
        $arguments[] = $mockArgument;
        $this->assertTrue(isset($arguments['argumentName1234']), 'isset() did not return TRUE.');
    }

    /**
     * @test
     */
    public function getArgumentNamesReturnsNamesOfAddedArguments()
    {
        $mockArgument1 = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);
        $mockArgument1->expects($this->any())->method('getName')->will($this->returnValue('argumentName1'));
        $mockArgument2 = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);
        $mockArgument2->expects($this->any())->method('getName')->will($this->returnValue('argumentName2'));
        $mockArgument3 = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);
        $mockArgument3->expects($this->any())->method('getName')->will($this->returnValue('argumentName3'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments[] = $mockArgument1;
        $arguments[] = $mockArgument2;
        $arguments[] = $mockArgument3;
        $expectedArgumentNames = ['argumentName1', 'argumentName2', 'argumentName3'];
        $this->assertEquals($expectedArgumentNames, $arguments->getArgumentNames(), 'Returned argument names were not as expected.');
    }

    /**
     * @test
     */
    public function getArgumentShortNamesReturnsShortNamesOfAddedArguments()
    {
        $mockArgument1 = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName', 'getShortName'], [], '', false);
        $mockArgument1->expects($this->any())->method('getName')->will($this->returnValue('argumentName1'));
        $mockArgument1->expects($this->any())->method('getShortName')->will($this->returnValue('a'));
        $mockArgument2 = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName', 'getShortName'], [], '', false);
        $mockArgument2->expects($this->any())->method('getName')->will($this->returnValue('argumentName2'));
        $mockArgument2->expects($this->any())->method('getShortName')->will($this->returnValue('b'));
        $mockArgument3 = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName', 'getShortName'], [], '', false);
        $mockArgument3->expects($this->any())->method('getName')->will($this->returnValue('argumentName3'));
        $mockArgument3->expects($this->any())->method('getShortName')->will($this->returnValue('c'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments[] = $mockArgument1;
        $arguments[] = $mockArgument2;
        $arguments[] = $mockArgument3;
        $expectedShortNames = ['a', 'b', 'c'];
        $this->assertEquals($expectedShortNames, $arguments->getArgumentShortNames(), 'Returned argument short names were not as expected.');
    }

    /**
     * @test
     */
    public function addNewArgumentCreatesAndAddsNewArgument()
    {
        $mockArgument = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);
        $mockArgument->expects($this->any())->method('getName')->will($this->returnValue('dummyName'));
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class)->will($this->returnValue($mockArgument));
        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $addedArgument = $arguments->addNewArgument('dummyName');
        $this->assertInstanceOf(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, $addedArgument, 'addNewArgument() either did not add a new argument or did not return it.');
        $retrievedArgument = $arguments['dummyName'];
        $this->assertSame($addedArgument, $retrievedArgument, 'The added and the retrieved argument are not the same.');
    }

    /**
     * @test
     */
    public function addNewArgumentAssumesTextDataTypeByDefault()
    {
        $mockArgument = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName'], [], '', false);
        $mockArgument->expects($this->any())->method('getName')->will($this->returnValue('dummyName'));
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, 'dummyName', 'Text')->will($this->returnValue($mockArgument));
        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $arguments->addNewArgument('dummyName');
    }

    /**
     * @test
     */
    public function addNewArgumentCanAddArgumentsMarkedAsRequired()
    {
        $mockArgument = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName', 'setRequired'], [], '', false);
        $mockArgument->expects($this->once())->method('getName')->will($this->returnValue('dummyName'));
        $mockArgument->expects($this->once())->method('setRequired')->with(true);
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, 'dummyName', 'Text')->will($this->returnValue($mockArgument));
        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $arguments->addNewArgument('dummyName', 'Text', true);
    }

    /**
     * @test
     */
    public function addNewArgumentCanAddArgumentsMarkedAsOptionalWithDefaultValues()
    {
        $mockArgument = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName', 'setRequired', 'setDefaultValue'], [], '', false);
        $mockArgument->expects($this->once())->method('getName')->will($this->returnValue('dummyName'));
        $mockArgument->expects($this->once())->method('setRequired')->with(false);
        $mockArgument->expects($this->once())->method('setDefaultValue')->with('someDefaultValue');
        $mockObjectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, 'dummyName', 'Text')->will($this->returnValue($mockArgument));
        $arguments = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class, ['dummy']);
        $arguments->_set('objectManager', $mockObjectManager);
        $arguments->addNewArgument('dummyName', 'Text', false, 'someDefaultValue');
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function callingInvalidMethodThrowsException()
    {
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments->nonExistingMethod();
    }

    /**
     * @test
     */
    public function removeAllClearsAllArguments()
    {
        $mockArgument1 = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName', 'getShortName'], [], '', false);
        $mockArgument1->expects($this->any())->method('getName')->will($this->returnValue('argumentName1'));
        $mockArgument2 = $this->getMock(\TYPO3\CMS\Extbase\Mvc\Controller\Argument::class, ['getName', 'getShortName'], [], '', false);
        $mockArgument2->expects($this->any())->method('getName')->will($this->returnValue('argumentName2'));
        $arguments = new \TYPO3\CMS\Extbase\Mvc\Controller\Arguments();
        $arguments[] = $mockArgument1;
        $arguments[] = $mockArgument2;
        $this->assertTrue($arguments->hasArgument('argumentName2'));
        $arguments->removeAll();
        $this->assertFalse($arguments->hasArgument('argumentName2'));
    }
}
