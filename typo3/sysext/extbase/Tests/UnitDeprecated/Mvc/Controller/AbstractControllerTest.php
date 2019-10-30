<?php
namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Mvc\Controller;

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
class AbstractControllerTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function addFlashMessageDataProvider()
    {
        return [
            [
                new \TYPO3\CMS\Core\Messaging\FlashMessage('Simple Message'),
                'Simple Message',
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                false
            ],
            [
                new \TYPO3\CMS\Core\Messaging\FlashMessage('Some OK', 'Message Title', \TYPO3\CMS\Core\Messaging\FlashMessage::OK, true),
                'Some OK',
                'Message Title',
                \TYPO3\CMS\Core\Messaging\FlashMessage::OK,
                true
            ],
            [
                new \TYPO3\CMS\Core\Messaging\FlashMessage('Some Info', 'Message Title', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO, true),
                'Some Info',
                'Message Title',
                \TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
                true
            ],
            [
                new \TYPO3\CMS\Core\Messaging\FlashMessage('Some Notice', 'Message Title', \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE, true),
                'Some Notice',
                'Message Title',
                \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE,
                true
            ],

            [
                new \TYPO3\CMS\Core\Messaging\FlashMessage('Some Warning', 'Message Title', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING, true),
                'Some Warning',
                'Message Title',
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING,
                true
            ],
            [
                new \TYPO3\CMS\Core\Messaging\FlashMessage('Some Error', 'Message Title', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR, true),
                'Some Error',
                'Message Title',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                true
            ]
        ];
    }

    /**
     * @test
     * @dataProvider addFlashMessageDataProvider
     */
    public function addFlashMessageAddsFlashMessageObjectToFlashMessageQueue($expectedMessage, $messageBody, $messageTitle = '', $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK, $storeInSession = true)
    {
        $flashMessageQueue = $this->getMockBuilder(\TYPO3\CMS\Core\Messaging\FlashMessageQueue::class)
            ->setMethods(['enqueue'])
            ->setConstructorArgs([$this->getUniqueId('identifier_')])
            ->getMock();

        $flashMessageQueue->expects(self::once())->method('enqueue')->with(self::equalTo($expectedMessage));

        $controllerContext = $this->getMockBuilder(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext::class)
            ->setMethods(['getFlashMessageQueue'])
            ->getMock();
        $controllerContext->expects(self::once())->method('getFlashMessageQueue')->willReturn($flashMessageQueue);

        $controller = $this->getAccessibleMockForAbstractClass(
            \TYPO3\CMS\Extbase\Mvc\Controller\AbstractController::class,
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
            \TYPO3\CMS\Extbase\Mvc\Controller\AbstractController::class,
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
