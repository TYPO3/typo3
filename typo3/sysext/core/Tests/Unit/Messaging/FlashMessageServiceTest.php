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

namespace TYPO3\CMS\Core\Tests\Unit\Messaging;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class FlashMessageServiceTest extends UnitTestCase
{
    /**
     * @var FlashMessageService|MockObject|AccessibleObjectInterface
     */
    protected $flashMessageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->flashMessageService = new FlashMessageService();
    }

    /**
     * @test
     */
    public function getMessageQueueByIdentifierRegistersNewFlashmessageQueuesOnlyOnce(): void
    {
        self::assertSame(
            $this->flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages'),
            $this->flashMessageService->getMessageQueueByIdentifier('core.template.flashMessages')
        );
    }
}
