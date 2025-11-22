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

namespace TYPO3\CMS\Core\Tests\Unit\Session;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SessionManagerTest extends UnitTestCase
{
    #[Test]
    public function getSessionBackendThrowsExceptionForMissingConfiguration(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1482234750);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['myNewIdentifier'] = 'I am not an array';
        $subject = new SessionManager();
        $subject->getSessionBackend('myNewidentifier');
    }

    #[Test]
    public function getSessionBackendThrowsExceptionIfBackendDoesNotImplementInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1482235035);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['myidentifier'] = [
            'backend'  => \stdClass::class,
            'options' => [],
        ];
        (new SessionManager())->getSessionBackend('myidentifier');
    }
}
