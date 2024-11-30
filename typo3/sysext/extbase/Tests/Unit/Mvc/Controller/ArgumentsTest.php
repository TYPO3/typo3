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

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Mvc\Controller\Argument;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ArgumentsTest extends UnitTestCase
{
    #[Test]
    public function argumentsObjectIsOfScopePrototype(): void
    {
        $arguments1 = new Arguments();
        $arguments2 = new Arguments();
        self::assertNotSame($arguments1, $arguments2, 'The arguments object is not of scope prototype!');
    }

    #[Test]
    public function addingAnArgumentManuallyWorks(): void
    {
        $arguments = new Arguments();
        $newArgument = new Argument('argumentName1234', 'dummyValue');
        $arguments->addArgument($newArgument);
        self::assertSame($newArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
    }

    #[Test]
    public function addingAnArgumentReplacesArgumentWithSameName(): void
    {
        $arguments = new Arguments();
        $mockFirstArgument = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockFirstArgument->method('getName')->willReturn('argumentName1234');
        $arguments->addArgument($mockFirstArgument);
        $mockSecondArgument = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockSecondArgument->method('getName')->willReturn('argumentName1234');
        $arguments->addArgument($mockSecondArgument);
        self::assertSame($mockSecondArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
    }

    #[Test]
    public function addNewArgumentProvidesFluentInterface(): void
    {
        $arguments = new Arguments();
        $newArgument = $arguments->addNewArgument('someArgument');
        self::assertInstanceOf(Argument::class, $newArgument);
    }

    #[Test]
    public function addingArgumentThroughArrayAccessWorks(): void
    {
        $mockArgument = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->method('getName')->willReturn('argumentName1234');
        $arguments = new Arguments();
        $arguments[] = $mockArgument;
        self::assertTrue($arguments->hasArgument('argumentName1234'), 'Added argument does not exist.');
        self::assertSame($mockArgument, $arguments->getArgument('argumentName1234'), 'Added and retrieved arguments are not the same.');
    }

    #[Test]
    public function retrievingArgumentThroughArrayAccessWorks(): void
    {
        $mockArgument = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->method('getName')->willReturn('argumentName1234');
        $arguments = new Arguments();
        $arguments[] = $mockArgument;
        self::assertSame($mockArgument, $arguments['argumentName1234'], 'Argument retrieved by array access is not the one we added.');
    }

    #[Test]
    public function getArgumentWithNonExistingArgumentNameThrowsException(): void
    {
        $this->expectException(NoSuchArgumentException::class);
        $this->expectExceptionCode(1195815178);
        $arguments = new Arguments();
        $arguments->getArgument('someArgument');
    }

    #[Test]
    public function issetReturnsCorrectResult(): void
    {
        $mockArgument = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->method('getName')->willReturn('argumentName1234');
        $arguments = new Arguments();
        self::assertFalse(isset($arguments['argumentName1234']), 'isset() did not return FALSE.');
        $arguments[] = $mockArgument;
        self::assertTrue(isset($arguments['argumentName1234']), 'isset() did not return TRUE.');
    }

    #[Test]
    public function getArgumentNamesReturnsNamesOfAddedArguments(): void
    {
        $mockArgument1 = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument1->method('getName')->willReturn('argumentName1');
        $mockArgument2 = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument2->method('getName')->willReturn('argumentName2');
        $mockArgument3 = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument3->method('getName')->willReturn('argumentName3');
        $arguments = new Arguments();
        $arguments[] = $mockArgument1;
        $arguments[] = $mockArgument2;
        $arguments[] = $mockArgument3;
        $expectedArgumentNames = ['argumentName1', 'argumentName2', 'argumentName3'];
        self::assertEquals($expectedArgumentNames, $arguments->getArgumentNames(), 'Returned argument names were not as expected.');
    }

    #[Test]
    public function getArgumentShortNamesReturnsShortNamesOfAddedArguments(): void
    {
        $mockArgument1 = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName', 'getShortName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument1->method('getName')->willReturn('argumentName1');
        $mockArgument1->method('getShortName')->willReturn('a');
        $mockArgument2 = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName', 'getShortName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument2->method('getName')->willReturn('argumentName2');
        $mockArgument2->method('getShortName')->willReturn('b');
        $mockArgument3 = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName', 'getShortName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument3->method('getName')->willReturn('argumentName3');
        $mockArgument3->method('getShortName')->willReturn('c');
        $arguments = new Arguments();
        $arguments[] = $mockArgument1;
        $arguments[] = $mockArgument2;
        $arguments[] = $mockArgument3;
        $expectedShortNames = ['a', 'b', 'c'];
        self::assertEquals($expectedShortNames, $arguments->getArgumentShortNames(), 'Returned argument short names were not as expected.');
    }

    #[Test]
    public function addNewArgumentCreatesAndAddsNewArgument(): void
    {
        $mockArgument = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument->method('getName')->willReturn('dummyName');
        $arguments = new Arguments();
        $addedArgument = $arguments->addNewArgument('dummyName');
        self::assertInstanceOf(Argument::class, $addedArgument, 'addNewArgument() either did not add a new argument or did not return it.');
        $retrievedArgument = $arguments['dummyName'];
        self::assertSame($addedArgument, $retrievedArgument, 'The added and the retrieved argument are not the same.');
    }

    #[Test]
    public function addNewArgumentCanAddArgumentsMarkedAsRequired(): void
    {
        $arguments = new Arguments();
        $argument = $arguments->addNewArgument('dummyName', 'Text', true);
        self::assertTrue($argument->isRequired());
    }

    #[Test]
    public function addNewArgumentCanAddArgumentsMarkedAsOptionalWithDefaultValues(): void
    {
        $arguments = new Arguments();
        $argument = $arguments->addNewArgument('dummyName', 'Text', false, 'someDefaultValue');
        self::assertFalse($argument->isRequired());
    }

    #[Test]
    public function callingInvalidMethodThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1210858451);
        $arguments = new Arguments();
        $arguments->nonExistingMethod();
    }

    #[Test]
    public function removeAllClearsAllArguments(): void
    {
        $mockArgument1 = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName', 'getShortName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument1->method('getName')->willReturn('argumentName1');
        $mockArgument2 = $this->getMockBuilder(Argument::class)
            ->onlyMethods(['getName', 'getShortName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockArgument2->method('getName')->willReturn('argumentName2');
        $arguments = new Arguments();
        $arguments[] = $mockArgument1;
        $arguments[] = $mockArgument2;
        self::assertTrue($arguments->hasArgument('argumentName2'));
        $arguments->removeAll();
        self::assertFalse($arguments->hasArgument('argumentName2'));
    }
}
