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
        $firstArgument = new Argument('argumentName1234', 'string');
        $arguments->addArgument($firstArgument);
        $secondArgument = new Argument('argumentName1234', 'string');
        $arguments->addArgument($secondArgument);
        self::assertSame($secondArgument, $arguments->getArgument('argumentName1234'), 'The added and retrieved argument is not the same.');
    }

    #[Test]
    public function addNewArgumentCreatesArgumentWithGivenName(): void
    {
        $arguments = new Arguments();
        $newArgument = $arguments->addNewArgument('someArgument');
        self::assertEquals('someArgument', $newArgument->getName());
    }

    #[Test]
    public function addingArgumentThroughArrayAccessWorks(): void
    {
        $argument = new Argument('argumentName1234', 'string');
        $arguments = new Arguments();
        $arguments[] = $argument;
        self::assertTrue($arguments->hasArgument('argumentName1234'), 'Added argument does not exist.');
        self::assertSame($argument, $arguments->getArgument('argumentName1234'), 'Added and retrieved arguments are not the same.');
    }

    #[Test]
    public function retrievingArgumentThroughArrayAccessWorks(): void
    {
        $argument = new Argument('argumentName1234', 'string');
        $arguments = new Arguments();
        $arguments[] = $argument;
        self::assertSame($argument, $arguments['argumentName1234'], 'Argument retrieved by array access is not the one we added.');
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
        $argument = new Argument('argumentName1234', 'string');
        $arguments = new Arguments();
        self::assertFalse(isset($arguments['argumentName1234']), 'isset() did not return FALSE.');
        $arguments[] = $argument;
        self::assertTrue(isset($arguments['argumentName1234']), 'isset() did not return TRUE.');
    }

    #[Test]
    public function getArgumentNamesReturnsNamesOfAddedArguments(): void
    {
        $argument1 = new Argument('argumentName1', 'string');
        $argument2 = new Argument('argumentName2', 'string');
        $argument3 = new Argument('argumentName3', 'string');
        $arguments = new Arguments();
        $arguments[] = $argument1;
        $arguments[] = $argument2;
        $arguments[] = $argument3;
        $expectedArgumentNames = ['argumentName1', 'argumentName2', 'argumentName3'];
        self::assertEquals($expectedArgumentNames, $arguments->getArgumentNames(), 'Returned argument names were not as expected.');
    }

    #[Test]
    public function getArgumentShortNamesReturnsShortNamesOfAddedArguments(): void
    {
        $argument1 = new Argument('argumentName1', 'string');
        $argument1->setShortName('a');
        $argument2 = new Argument('argumentName2', 'string');
        $argument2->setShortName('b');
        $argument3 = new Argument('argumentName3', 'string');
        $argument3->setShortName('c');
        $arguments = new Arguments();
        $arguments[] = $argument1;
        $arguments[] = $argument2;
        $arguments[] = $argument3;
        $expectedShortNames = ['a', 'b', 'c'];
        self::assertEquals($expectedShortNames, $arguments->getArgumentShortNames(), 'Returned argument short names were not as expected.');
    }

    #[Test]
    public function addNewArgumentCreatesAndAddsNewArgument(): void
    {
        $arguments = new Arguments();
        $addedArgument = $arguments->addNewArgument('dummyName');
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
        /** @phpstan-ignore-next-line method.notFound */
        $arguments->nonExistingMethod();
    }

    #[Test]
    public function removeAllClearsAllArguments(): void
    {
        $argument1 = new Argument('argumentName1', 'string');
        $argument2 = new Argument('argumentName2', 'string');
        $arguments = new Arguments();
        $arguments[] = $argument1;
        $arguments[] = $argument2;
        self::assertTrue($arguments->hasArgument('argumentName2'));
        $arguments->removeAll();
        self::assertFalse($arguments->hasArgument('argumentName2'));
    }
}
