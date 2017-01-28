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

use TYPO3\Components\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper;

/**
 * Testcase for FlashMessagesViewHelper
 */
class FlashMessagesViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper
     */
    protected $viewHelper;

    /**
     * @var FlashMessageQueue
     */
    protected $flashMessageQueue;

    /**
     * Sets up this test case
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->flashMessageQueue = $this->prophesize(FlashMessageQueue::class);
        $this->controllerContext->expects($this->any())->method('getFlashMessageQueue')->will($this->returnValue($this->flashMessageQueue->reveal()));

        $this->viewHelper = new FlashMessagesViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfNoFlashMessagesAreInQueue()
    {
        $this->flashMessageQueue->getAllMessagesAndFlush()->willReturn();
        $this->assertEmpty($this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function fetchMessagesFromSpecificQueue()
    {
        $queueIdentifier = 'myQueue';

        $this->flashMessageQueue->getAllMessagesAndFlush()->willReturn();
        $this->controllerContext->expects($this->once())->method('getFlashMessageQueue')->with($queueIdentifier)->will($this->returnValue($this->flashMessageQueue->reveal()));

        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'queueIdentifier' => $queueIdentifier
            ]
        );

        $this->assertEmpty($this->viewHelper->initializeArgumentsAndRender());
    }

    /**
     * @test
     */
    public function parameterAsStartsRenderingOnTemplate()
    {
        $this->viewHelper->setRenderChildrenClosure(function () {
            return 'a simple String';
        });

        $flashMessage = new FlashMessage('test message body', 'test message title');

        $this->flashMessageQueue->getAllMessagesAndFlush()->willReturn([$flashMessage]);
        $this->setArgumentsUnderTest(
            $this->viewHelper,
            [
                'as' => 'flashy',
            ]
        );
        $actualResult = $this->viewHelper->initializeArgumentsAndRender();

        $this->assertEquals('a simple String', $actualResult);
    }
}
