<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Error;

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
class MessageTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function theConstructorSetsTheMessageMessageCorrectly()
    {
        $messageMessage = 'The message';
        $error = new \TYPO3\CMS\Extbase\Error\Message($messageMessage, 0);
        $this->assertEquals($messageMessage, $error->getMessage());
    }

    /**
     * @test
     */
    public function theConstructorSetsTheMessageCodeCorrectly()
    {
        $messageCode = 123456789;
        $error = new \TYPO3\CMS\Extbase\Error\Message('', $messageCode);
        $this->assertEquals($messageCode, $error->getCode());
    }

    /**
     * @test
     */
    public function theConstructorSetsTheMessageArgumentsCorrectly()
    {
        $messageArguments = ['foo', 'bar'];
        $error = new \TYPO3\CMS\Extbase\Error\Message('', 1, $messageArguments);
        $this->assertEquals($messageArguments, $error->getArguments());
    }

    /**
     * @test
     */
    public function theConstructorSetsTheMessageTitleCorrectly()
    {
        $messageTitle = 'Title';
        $error = new \TYPO3\CMS\Extbase\Error\Message('', 1, [], $messageTitle);
        $this->assertEquals($messageTitle, $error->getTitle());
    }

    /**
     * @test
     */
    public function renderRendersCorrectlyWithoutArguments()
    {
        $error = new \TYPO3\CMS\Extbase\Error\Message('Message', 1);
        $this->assertEquals('Message', $error->render());
    }

    /**
     * @test
     */
    public function renderRendersCorrectlyWithArguments()
    {
        $error = new \TYPO3\CMS\Extbase\Error\Message('Foo is %s and Bar is %s', 1, ['baz', 'qux']);
        $this->assertEquals('Foo is baz and Bar is qux', $error->render());
    }

    /**
     * @test
     */
    public function toStringCallsRender()
    {
        $error = new \TYPO3\CMS\Extbase\Error\Message('Foo is %s and Bar is %s', 1, ['baz', 'qux']);
        $this->assertEquals('Foo is baz and Bar is qux', $error);
    }
}
