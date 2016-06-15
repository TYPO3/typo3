<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers;

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
 * Testcase for FlashMessagesViewHelper
 */
class FlashMessagesViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper| \PHPUnit_Framework_MockObject_MockObject |\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $viewHelper;

    /**
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder
     */
    protected $mockTagBuilder;

    /**
     * @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue|\PHPUnit_Framework_MockObject_MockObject $mockFlashMessagingQueue
     */
    protected $mockFlashMessagingQueue;

    /**
     * Sets up this test case
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue|\PHPUnit_Framework_MockObject_MockObject $mockFlashMessagingQueue */
        $mockFlashMessagingQueue = $this->getMockBuilder(\TYPO3\CMS\Core\Messaging\FlashMessageQueue::class)
            ->setMethods(array('getAllMessagesAndFlush'))
            ->setConstructorArgs(array('foo'))
            ->getMock();
        $mockFlashMessagingQueue->expects($this->once())->method('getAllMessagesAndFlush')->will($this->returnValue(array()));
        $this->mockFlashMessagingQueue = $mockFlashMessagingQueue;

        $this->controllerContext->expects($this->any())->method('getFlashMessageQueue')->will($this->returnValue($mockFlashMessagingQueue));

        $this->mockTagBuilder = $this->createMock(\TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder::class);
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper::class, array('dummy'));
        $this->viewHelper->_set('tag', $this->mockTagBuilder);
        $this->viewHelper->setRenderingContext($this->renderingContext);
        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfNoFlashMessagesAreInQueue()
    {
        $this->assertEmpty($this->viewHelper->render());
    }

    /**
     * @test
     */
    public function fetchMessagesFromSpecificQueue()
    {
        $queueIdentifier = 'myQueue';

        $this->controllerContext->expects($this->once())->method('getFlashMessageQueue')->with($queueIdentifier)->will($this->returnValue($this->mockFlashMessagingQueue));

        $this->viewHelper->setArguments(array('queueIdentifier' => $queueIdentifier));

        $this->assertEmpty($this->viewHelper->render());
    }
}
