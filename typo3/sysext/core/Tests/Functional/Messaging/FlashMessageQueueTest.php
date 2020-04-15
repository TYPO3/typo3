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

namespace TYPO3\CMS\Core\Tests\Functional\Messaging;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class FlashMessageQueueTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function getAllMessagesContainsEnqueuedMessage()
    {
        $this->setUpBackendUserFromFixture(1);
        $flashMessage = new FlashMessage('Foo', 'Bar', FlashMessage::OK, true);
        $flashMessageQueue = new FlashMessageQueue('core.template.flashMessages');
        $flashMessageQueue->addMessage($flashMessage);
        self::assertEquals([$flashMessage], $flashMessageQueue->getAllMessages());
    }

    /**
     * @test
     */
    public function messagesCanBeFilteredBySeverity()
    {
        $this->setUpBackendUserFromFixture(1);
        $flashMessageQueue = new FlashMessageQueue('core.template.flashMessages');
        $messages = [
            0 => new FlashMessage('This is a test message', '1', FlashMessage::NOTICE),
            1 => new FlashMessage('This is another test message', '2', FlashMessage::WARNING)
        ];
        $flashMessageQueue->enqueue($messages[0]);
        $flashMessageQueue->enqueue($messages[1]);

        $filteredFlashMessages = $flashMessageQueue->getAllMessages(FlashMessage::NOTICE);

        self::assertEquals(count($filteredFlashMessages), 1);

        reset($filteredFlashMessages);
        $flashMessage = current($filteredFlashMessages);
        self::assertEquals($messages[0], $flashMessage);
    }

    /**
     * @test
     */
    public function getAllMessagesAndFlushContainsEnqueuedMessage()
    {
        $this->setUpBackendUserFromFixture(1);
        $flashMessage = new FlashMessage('Foo', 'Bar', FlashMessage::OK, true);
        $flashMessageQueue = new FlashMessageQueue('core.template.flashMessages');
        $flashMessageQueue->addMessage($flashMessage);
        self::assertEquals([$flashMessage], $flashMessageQueue->getAllMessagesAndFlush());
    }

    /**
     * @test
     */
    public function getAllMessagesAndFlushClearsSessionStack()
    {
        $this->setUpBackendUserFromFixture(1);
        $flashMessage = new FlashMessage('Foo', 'Bar', FlashMessage::OK, true);
        $flashMessageQueue = new FlashMessageQueue('core.template.flashMessages');
        $flashMessageQueue->addMessage($flashMessage);
        $flashMessageQueue->getAllMessagesAndFlush();
        self::assertEquals([], $flashMessageQueue->getAllMessagesAndFlush());
    }

    /**
     * @test
     */
    public function getMessagesAndFlushCanFilterBySeverity()
    {
        $this->setUpBackendUserFromFixture(1);
        $flashMessageQueue = new FlashMessageQueue('core.template.flashMessages');
        $messages = [
            0 => new FlashMessage('This is a test message', '1', FlashMessage::NOTICE),
            1 => new FlashMessage('This is another test message', '2', FlashMessage::WARNING)
        ];
        $flashMessageQueue->addMessage($messages[0]);
        $flashMessageQueue->addMessage($messages[1]);

        $filteredFlashMessages = $flashMessageQueue->getAllMessagesAndFlush(FlashMessage::NOTICE);

        self::assertEquals(count($filteredFlashMessages), 1);

        reset($filteredFlashMessages);
        $flashMessage = current($filteredFlashMessages);
        self::assertEquals($messages[0], $flashMessage);

        self::assertEquals([], $flashMessageQueue->getAllMessages(FlashMessage::NOTICE));
        self::assertEquals([$messages[1]], array_values($flashMessageQueue->getAllMessages()));
    }

    /**
     * @test
     */
    public function getAllMessagesReturnsSessionFlashMessageAndTransientFlashMessage()
    {
        $this->setUpBackendUserFromFixture(1);
        $flashMessageQueue = new FlashMessageQueue('core.template.flashMessages');
        $flashMessage1 = new FlashMessage('Transient', 'Title', FlashMessage::OK, false);
        $flashMessage2 = new FlashMessage('Session', 'Title', FlashMessage::OK, true);
        $flashMessageQueue->addMessage($flashMessage1);
        $flashMessageQueue->addMessage($flashMessage2);

        self::assertCount(2, $flashMessageQueue->getAllMessages());
    }

    /**
     * @test
     */
    public function clearClearsTheQueue()
    {
        $this->setUpBackendUserFromFixture(1);
        $flashMessage = new FlashMessage('Foo', 'Bar', FlashMessage::OK, true);
        $flashMessageQueue = new FlashMessageQueue('core.template.flashMessages');
        $flashMessageQueue->addMessage($flashMessage);
        $flashMessageQueue->clear();
        self::assertCount(0, $flashMessageQueue);
    }

    /**
     * @test
     */
    public function toArrayOnlyRespectsTransientFlashMessages()
    {
        $this->setUpBackendUserFromFixture(1);
        $flashMessageQueue = new FlashMessageQueue('core.template.flashMessages');
        $flashMessage1 = new FlashMessage('Transient', 'Title', FlashMessage::OK, false);
        $flashMessage2 = new FlashMessage('Session', 'Title', FlashMessage::OK, true);
        $flashMessageQueue->addMessage($flashMessage1);
        $flashMessageQueue->addMessage($flashMessage2);

        self::assertCount(1, $flashMessageQueue);
    }
}
