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

namespace TYPO3\CMS\Extbase\Tests\Unit\Error;

use TYPO3\CMS\Extbase\Error\Message;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MessageTest extends UnitTestCase
{
    /**
     * @test
     */
    public function theConstructorSetsTheMessageMessageCorrectly()
    {
        $messageMessage = 'The message';
        $error = new Message($messageMessage, 0);
        self::assertEquals($messageMessage, $error->getMessage());
    }

    /**
     * @test
     */
    public function theConstructorSetsTheMessageCodeCorrectly()
    {
        $messageCode = 123456789;
        $error = new Message('', $messageCode);
        self::assertEquals($messageCode, $error->getCode());
    }

    /**
     * @test
     */
    public function theConstructorSetsTheMessageArgumentsCorrectly()
    {
        $messageArguments = ['foo', 'bar'];
        $error = new Message('', 1, $messageArguments);
        self::assertEquals($messageArguments, $error->getArguments());
    }

    /**
     * @test
     */
    public function theConstructorSetsTheMessageTitleCorrectly()
    {
        $messageTitle = 'Title';
        $error = new Message('', 1, [], $messageTitle);
        self::assertEquals($messageTitle, $error->getTitle());
    }

    /**
     * @test
     */
    public function renderRendersCorrectlyWithoutArguments()
    {
        $error = new Message('Message', 1);
        self::assertEquals('Message', $error->render());
    }

    /**
     * @test
     */
    public function renderRendersCorrectlyWithArguments()
    {
        $error = new Message('Foo is %s and Bar is %s', 1, ['baz', 'qux']);
        self::assertEquals('Foo is baz and Bar is qux', $error->render());
    }

    /**
     * @test
     */
    public function toStringCallsRender()
    {
        $error = new Message('Foo is %s and Bar is %s', 1, ['baz', 'qux']);
        self::assertEquals('Foo is baz and Bar is qux', $error);
    }
}
