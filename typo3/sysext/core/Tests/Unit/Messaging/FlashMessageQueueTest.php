<?php
namespace TYPO3\CMS\Core\Tests\Unit\Messaging;

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
class FlashMessageQueueTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $flashMessageQueue;

    /**
     * @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendUser;

    protected function setUp()
    {
        $this->frontendUser = $this->getMock(\TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::class, ['dummy']);
        $this->flashMessageQueue = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Messaging\FlashMessageQueue::class,
            ['getUserByContext'],
            ['core.template.flashMessages']
        );

        $this->flashMessageQueue->expects($this->any())->method('getUserByContext')->will($this->returnValue($this->frontendUser));
    }

    /**
     * @test
     */
    public function userSessionInitiallyIsEmpty()
    {
        $this->assertSame([], $this->flashMessageQueue->_call('getFlashMessagesFromSession'));
    }

    /**
     * @test
     */
    public function enqueueTransientFlashMessageKeepsSessionEmpty()
    {
        $this->flashMessageQueue->enqueue(new \TYPO3\CMS\Core\Messaging\FlashMessage('Foo', 'Bar', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, false));

        $this->assertSame([], $this->flashMessageQueue->_call('getFlashMessagesFromSession'));
    }

    /**
     * @test
     */
    public function enqueueSessionFlashMessageWritesSessionEntry()
    {
        $flashMessage = new \TYPO3\CMS\Core\Messaging\FlashMessage('Foo', 'Bar', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, true);
        $this->flashMessageQueue->enqueue($flashMessage);

        $this->assertSame([$flashMessage], $this->flashMessageQueue->_call('getFlashMessagesFromSession'));
    }

    /**
     * @test
     */
    public function getAllMessagesReturnsSessionFlashMessageAndTransientFlashMessage()
    {
        $flashMessage1 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, false);
        $flashMessage2 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Session', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, true);
        $this->flashMessageQueue->enqueue($flashMessage1);
        $this->flashMessageQueue->enqueue($flashMessage2);

        $this->assertCount(2, $this->flashMessageQueue->getAllMessages());
    }

    /**
     * @test
     */
    public function clearClearsTheQueue()
    {
        $flashMessage1 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, false);
        $flashMessage2 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, false);
        $this->flashMessageQueue->enqueue($flashMessage1);
        $this->flashMessageQueue->enqueue($flashMessage2);
        $this->flashMessageQueue->clear();

        $this->assertSame(0, $this->flashMessageQueue->count());
    }

    /**
     * @test
     */
    public function toArrayOnlyRespectsTransientFlashMessages()
    {
        $flashMessage1 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, false);
        $flashMessage2 = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, true);
        $this->flashMessageQueue->enqueue($flashMessage1);
        $this->flashMessageQueue->enqueue($flashMessage2);

        $this->assertCount(1, $this->flashMessageQueue->toArray());
    }

    /**
     * @test
     */
    public function toArrayReturnsEmptyArrayWithForEmptyQueue()
    {
        $this->assertSame([], $this->flashMessageQueue->toArray());
    }

    /**
     * @test
     */
    public function getAllMessagesAndFlushClearsSessionStack()
    {
        $flashMessage = new \TYPO3\CMS\Core\Messaging\FlashMessage('Transient', 'Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, true);
        $this->flashMessageQueue->enqueue($flashMessage);
        $this->flashMessageQueue->getAllMessagesAndFlush();

        /** @var $frontendUser \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication */
        $frontendUser = $this->flashMessageQueue->_call('getUserByContext');

        $this->assertNull($frontendUser->getSessionData('core.template.flashMessages'));
    }

    /**
     * @test
     */
    public function messagesCanBeFilteredBySeverity()
    {
        $messages = [
            0 => new \TYPO3\CMS\Core\Messaging\FlashMessage('This is a test message', 1, \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE),
            1 => new \TYPO3\CMS\Core\Messaging\FlashMessage('This is another test message', 2, \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING)
        ];
        $this->flashMessageQueue->enqueue($messages[0]);
        $this->flashMessageQueue->enqueue($messages[1]);

        $filteredFlashMessages = $this->flashMessageQueue->getAllMessages(\TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE);

        $this->assertEquals(count($filteredFlashMessages), 1);

        reset($filteredFlashMessages);
        $flashMessage = current($filteredFlashMessages);
        $this->assertEquals($messages[0], $flashMessage);
    }

    /**
     * @test
     */
    public function getMessagesAndFlushCanAlsoFilterBySeverity()
    {
        $messages = [
            0 => new \TYPO3\CMS\Core\Messaging\FlashMessage('This is a test message', 1, \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE),
            1 => new \TYPO3\CMS\Core\Messaging\FlashMessage('This is another test message', 2, \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING)
        ];
        $this->flashMessageQueue->enqueue($messages[0]);
        $this->flashMessageQueue->enqueue($messages[1]);

        $filteredFlashMessages = $this->flashMessageQueue->getAllMessagesAndFlush(\TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE);

        $this->assertEquals(count($filteredFlashMessages), 1);

        reset($filteredFlashMessages);
        $flashMessage = current($filteredFlashMessages);
        $this->assertEquals($messages[0], $flashMessage);

        $this->assertEquals([], $this->flashMessageQueue->getAllMessages(\TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE));
        $this->assertEquals([$messages[1]], array_values($this->flashMessageQueue->getAllMessages()));
    }
}
