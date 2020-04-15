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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Mvc\Controller;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\AbstractController;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractControllerTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function addFlashMessageDataProvider()
    {
        return [
            [
                new FlashMessage('Simple Message'),
                'Simple Message',
                '',
                FlashMessage::OK,
                false
            ],
            [
                new FlashMessage('Some OK', 'Message Title', FlashMessage::OK, true),
                'Some OK',
                'Message Title',
                FlashMessage::OK,
                true
            ],
            [
                new FlashMessage('Some Info', 'Message Title', FlashMessage::INFO, true),
                'Some Info',
                'Message Title',
                FlashMessage::INFO,
                true
            ],
            [
                new FlashMessage('Some Notice', 'Message Title', FlashMessage::NOTICE, true),
                'Some Notice',
                'Message Title',
                FlashMessage::NOTICE,
                true
            ],

            [
                new FlashMessage('Some Warning', 'Message Title', FlashMessage::WARNING, true),
                'Some Warning',
                'Message Title',
                FlashMessage::WARNING,
                true
            ],
            [
                new FlashMessage('Some Error', 'Message Title', FlashMessage::ERROR, true),
                'Some Error',
                'Message Title',
                FlashMessage::ERROR,
                true
            ]
        ];
    }

    /**
     * @test
     * @dataProvider addFlashMessageDataProvider
     */
    public function addFlashMessageAddsFlashMessageObjectToFlashMessageQueue($expectedMessage, $messageBody, $messageTitle = '', $severity = FlashMessage::OK, $storeInSession = true)
    {
        $flashMessageQueue = $this->getMockBuilder(FlashMessageQueue::class)
            ->setMethods(['enqueue'])
            ->setConstructorArgs([StringUtility::getUniqueId('identifier_')])
            ->getMock();

        $flashMessageQueue->expects(self::once())->method('enqueue')->with(self::equalTo($expectedMessage));

        $controllerContext = $this->getMockBuilder(ControllerContext::class)
            ->setMethods(['getFlashMessageQueue'])
            ->getMock();
        $controllerContext->expects(self::once())->method('getFlashMessageQueue')->willReturn($flashMessageQueue);

        $controller = $this->getAccessibleMockForAbstractClass(
            AbstractController::class,
            [],
            '',
            false,
            true,
            true,
            ['dummy']
        );
        $controller->_set('controllerContext', $controllerContext);

        $controller->addFlashMessage($messageBody, $messageTitle, $severity, $storeInSession);
    }

    /**
     * @test
     */
    public function addFlashMessageThrowsExceptionOnInvalidMessageBody()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1243258395);
        $controller = $this->getMockForAbstractClass(
            AbstractController::class,
            [],
            '',
            false,
            true,
            true,
            ['dummy']
        );

        $controller->addFlashMessage(new \stdClass());
    }
}
