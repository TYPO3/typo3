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
class FlashMessageServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Messaging\FlashMessageService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $flashMessageService;

    protected function setUp()
    {
        $this->flashMessageService = $this->getAccessibleMock(\TYPO3\CMS\Core\Messaging\FlashMessageService::class, ['dummy']);
    }

    /**
     * @test
     */
    public function flashMessageServiceInitiallyIsEmpty()
    {
        $this->assertSame([], $this->flashMessageService->_get('flashMessageQueues'));
    }

    /**
     * @test
     */
    public function getMessageQueueByIdentifierRegistersNewFlashmessageQueuesOnlyOnce()
    {
        $this->assertSame(
            $this->flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages'),
            $this->flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages')
        );
    }
}
