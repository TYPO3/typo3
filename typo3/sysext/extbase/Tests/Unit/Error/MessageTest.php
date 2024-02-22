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

namespace TYPO3\CMS\Extbase\Tests\Unit\Error;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Error\Message;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MessageTest extends UnitTestCase
{
    #[Test]
    public function theConstructorSetsTheMessageMessageCorrectly(): void
    {
        $messageMessage = 'The message';
        $error = new Message($messageMessage, 0);
        self::assertEquals($messageMessage, $error->getMessage());
    }

    #[Test]
    public function theConstructorSetsTheMessageCodeCorrectly(): void
    {
        $messageCode = 123456789;
        $error = new Message('', $messageCode);
        self::assertEquals($messageCode, $error->getCode());
    }

    #[Test]
    public function theConstructorSetsTheMessageArgumentsCorrectly(): void
    {
        $messageArguments = ['foo', 'bar'];
        $error = new Message('', 1, $messageArguments);
        self::assertEquals($messageArguments, $error->getArguments());
    }

    #[Test]
    public function theConstructorSetsTheMessageTitleCorrectly(): void
    {
        $messageTitle = 'Title';
        $error = new Message('', 1, [], $messageTitle);
        self::assertEquals($messageTitle, $error->getTitle());
    }

    #[Test]
    public function renderRendersCorrectlyWithoutArguments(): void
    {
        $error = new Message('Message', 1);
        self::assertEquals('Message', $error->render());
    }

    #[Test]
    public function renderRendersCorrectlyWithArguments(): void
    {
        $error = new Message('Foo is %s and Bar is %s', 1, ['baz', 'qux']);
        self::assertEquals('Foo is baz and Bar is qux', $error->render());
    }

    #[Test]
    public function toStringCallsRender(): void
    {
        $error = new Message('Foo is %s and Bar is %s', 1, ['baz', 'qux']);
        self::assertEquals('Foo is baz and Bar is qux', $error);
    }
}
